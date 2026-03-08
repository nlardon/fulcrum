/***********************************************************

*************************************************************/

#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h> // Pour décoder le JSON

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
    
    // 4. Envoyer une réponse de succès au script PHP
    server.send(200, "application/json", "{\"status\":\"success\", \"message\":\"Command processed\"}");
  } else {
    server.send(405, "text/plain", "Method Not Allowed");
  }
}

void setup(void) {
  Serial.begin(115200);
 
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
  //Serial.println(currentMillis);
  

  bool buttonState = digitalRead(2);

  if (buttonState == LOW) {
    Serial.println(millis());

    send_to_db(1,1);
    
  }

  delay(10);
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
