/***********************************************************

*************************************************************/

#include <SPI.h>
#include "nRF24L01.h"
#include "RF24.h"

#define SCK 18
#define MISO 19
#define MOSI 23

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
}

void loop(void) {
  while (radio.available()) {
    radio.read(&dataR, sizeof(dataR));
    Serial.print("Message recu : ");
    Serial.print(dataR.id);
    Serial.print(" - ");
    Serial.println(dataR.ans);

    send_to_db(dataR.id, 2);
  }


  bool buttonState = digitalRead(2);

  if (buttonState == LOW) {
    int16_t value = 1111;
    send_value(value);
    digitalWrite(3, HIGH);

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


  
}
