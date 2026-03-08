/***********************************************************
Station : controller ID + Colors
// Changelog
v21 - add pipe

*************************************************************/

#include <SPI.h>
#include "nRF24L01.h"
#include "RF24.h"
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h> // Pour décoder le JSON

#define SCK 18
#define MISO 19
#define MOSI 23

//const char* ssid = "Livebox-CF60";
//const char* password = "NrLfSv7X7gpKPUUNEQ";
//const char* ssid = "ciel-wifi-2";
//const char* password = "ciel-wifi";
const char* ssid = "Livebox-7CB0";
const char* password = "VW6aQrDzWuMNneKweP";


String address_handle_answer = "http://192.168.1.100/fulcrum/handle_answer_test.php";
//String address_handle_answer = "http://172.31.9.22/fulcrum/handle_answer_test.php";

WebServer server(80);

struct dataStruct {
  int16_t id;
  int16_t ans;
};

dataStruct dataR = { 99, 0 };
dataStruct dataT = { 0, 0 };

RF24 radio(4, 5);  //CE and CSN

//Create up to 6 pipe addresses P0 - P5;  the "LL" is for LongLong type
const uint64_t rAddress[] = {0x7878787878LL, 0xB3B4B5B6F1LL, 0xB3B4B5B6CDLL, 0xB3B4B5B6A3LL, 0xB3B4B5B60FLL, 0xB3B4B5B605LL };

int start = 0;
int stop = 0;

unsigned long currentMillis = 0;    // stores the value of millis() in each iteration of loop()

void handleCommand() {
  if (server.method() == HTTP_POST) {
    // 1. Lire le contenu du POST (le JSON)
    String postBody = server.arg("plain"); 
    
    // 2. Désérialiser le JSON
    DynamicJsonDocument doc(1024);
    DeserializationError error = deserializeJson(doc, postBody);

    if (error) {
      server.send(400, "application/json", "{\"status\":\"error\", \"message\":\"Invalid JSON\"}");
      return;
    }
    
    // 3. Extraire les données
    String command = doc["cmd"] | "N/A";
    
    Serial.println("Commande " + command + " reçue");
    if( command == "START") start = 1;
    if( command == "STOP") stop = 1;

    // 4. Envoyer une réponse de succès au script PHP
    server.send(200, "application/json", "{\"status\":\"success\", \"message\":\"Command processed\"}");
  } else {
    server.send(405, "text/plain", "Method Not Allowed");
  }
}

void setup(void) {
  SPI.begin(SCK, MISO, MOSI);
  Serial.begin(115200);
  Serial.println("Recepteur RF24");
  radio.begin();  //Start the nRF24 module
  radio.setPALevel(RF24_PA_LOW);  // "short range setting" - increase if you want more range AND have a good power supply
  radio.setDataRate(RF24_250KBPS); // Lower rate for better range/reliability
  radio.setChannel(108);          // the higher channels tend to be more "open"
  radio.setAutoAck(false);
  // Open up to six pipes for PRX to receive data
  radio.openReadingPipe(0,rAddress[0]);
  radio.openReadingPipe(1,rAddress[1]);
  radio.openReadingPipe(2,rAddress[2]);
  radio.openReadingPipe(3,rAddress[3]);
  radio.openReadingPipe(4,rAddress[4]);
  radio.openReadingPipe(5,rAddress[5]);
  radio.startListening();                 // Start listening for messagesradio.begin();
 
  pinMode(2, INPUT_PULLUP);
  pinMode(3, OUTPUT);

  WiFi.begin(ssid, password);
  Serial.println("Connecting");
  while(WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("");
  Serial.print("Connected to WiFi network with IP Address: ");
  Serial.println(WiFi.localIP());

  Serial.print("ESP32 IP Address: ");
  Serial.println(WiFi.localIP());

  // Configuration du point d'API (/command)
  server.on("/command", HTTP_POST, handleCommand);
  
  server.begin();
}

void loop(void) {
  server.handleClient();
  currentMillis = millis();
  //Serial.println(millis());
  byte pipeNum = 0; //variable to hold which writting pipe data

  while (radio.available()) {
    radio.read(&dataR, sizeof(dataR));
    Serial.print("Message recu : "); Serial.print(dataR.id); Serial.print(" - "); Serial.println(dataR.ans);
    send_to_db(dataR.id, dataR.ans);
  }

  bool buttonState = digitalRead(2);

  if (buttonState == LOW || start == 1 ) {
    // Switch to transmit mode
    int16_t value = 1111;
    digitalWrite(3, HIGH);
    for(int i=0;i<10;i++){
      send_value(value);
      delay(100);
    }
    start = 0;

  } else if (stop == 1) {
    //play = 0;
    int16_t value = 1010;
    // Switch to transmit mode
    for(int i=0;i<10;i++){
      send_value(value);
      delay(50);
    }
    digitalWrite(3, LOW);
    stop = 0;
  }

  delay(10);
}

void send_value(int16_t a) {
  radio.stopListening();
  radio.openWritingPipe(rAddress[0]); //Open writing pipe to the nRF24 that guessed the right number
  dataT.ans = a;
  Serial.print("J'envoie maintenant : ");
  Serial.print(dataT.id);
  Serial.print(" - ");
  Serial.println(dataT.ans);
  radio.write(&dataT, sizeof(dataT));  // émission du message via nRF24L01
  radio.startListening();
}

void recieved_value() {
  //  01+g
}

void send_to_db(int user, int answer){

  if(WiFi.status()== WL_CONNECTED){
    HTTPClient http;

   // String serverPath = ip_db + payload;

    http.begin(address_handle_answer.c_str());
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    // Data to send with HTTP POST
    String httpRequestData = "id_user=" + String(user) + "&answer_answer=" + String(answer);
      
    int httpResponseCode = http.POST(httpRequestData);
    
  }
  
}
