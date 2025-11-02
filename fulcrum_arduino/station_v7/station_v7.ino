/***********************************************************

*************************************************************/

#include <SPI.h>
#include "nRF24L01.h"
#include "RF24.h"
#include <WiFi.h>
#include <HTTPClient.h>

#define SCK 18
#define MISO 19
#define MOSI 23

const char* ssid = "Livebox-CF60";
const char* password = "NrLfSv7X7gpKPUUNEQ";

String address_handle_answer = "http://192.168.1.100/handle_answer_test.php";
//payload id_user=1&answer_answer=2

struct dataStruct {
  int16_t id;
  int16_t ans;
};

dataStruct dataR = { 99, 0 };
dataStruct dataT = { 0, 0 };

RF24 radio(4, 5);  //CE and CSN

const uint64_t adresseR = 0x1111111111;
const uint64_t addresseT = 0x1111111000;


void setup(void) {
  SPI.begin(SCK, MISO, MOSI);
  Serial.begin(115200);
  Serial.println("Recepteur RF24");
  radio.begin();
  //radio.setAutoAck(false);
  radio.openWritingPipe(addresseT);
  radio.openReadingPipe(1, adresseR);
  radio.startListening();

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
}

void loop(void) {
  while (radio.available()) {
    radio.read(&dataR, sizeof(dataR));
    Serial.print("Message recu : ");
    Serial.print(dataR.id);
    Serial.print(" - ");
    Serial.println(dataR.ans);

    send_to_db(dataR.id, dataR.ans);
  }


  bool buttonState = digitalRead(2);

  if (buttonState == LOW) {
    int16_t value = 1111;
    send_value(value);
    digitalWrite(3, HIGH);
    //send_to_db(random(1,4), random(1,4));

  } else {
    digitalWrite(3, LOW);
  }

  delay(10);
}

void send_value(int16_t a) {
  radio.stopListening();
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
