/*******************************************************************
Remote : controller ID + Colors
********************************************************************/

#include <SPI.h>
#include "nRF24L01.h"
#include "RF24.h"
#include <FastLED.h>
#define NUM_LEDS 5
#define DATA_PIN 2
int16_t num_remote = 3;

// Define the array of leds
CRGB leds[NUM_LEDS];

struct dataStruct
{
  int16_t id;
  int16_t ans;  
};

dataStruct dataR = {0,0};
dataStruct dataT = {num_remote,0};

RF24 radio(9, 10);  //CE and CSN
const uint64_t addresseT = 0x1111111111;
const uint64_t addresseR = 0x1111111000;

bool play_time = false;

int button_blue = 4;
int button_green = 5;
int button_yellow = 6;
int button_red = 7;
int button_middle = 8;


void setup(void)
{
  Serial.begin(115200);
  Serial.println("Emetteur de donnees");
  radio.begin();
  radio.setAutoAck(false);
  radio.openWritingPipe(addresseT);
  radio.openReadingPipe(1, addresseR);
  radio.startListening();
  init_led_button();
  //dataR.ans="p";
}

void loop(void){
//snake_led();
  while ( radio.available() ){
    radio.read( &dataR,  sizeof(dataR) );
    Serial.print("Message recu : "); Serial.print(dataR.id); Serial.print(" - "); Serial.println(dataR.ans); 
  }
//play_time = true;
  if(dataR.ans==1111){
  play_time = true;
  dataR.ans=0;
  }
  else if(dataR.ans==1010){
  play_time = false;
  dataR.ans=0;
  }
  play();


  delay(10);

}


void send_value(int16_t a)
{
  radio.stopListening();
  dataT.ans = a;
  Serial.print("J'envoie maintenant : "); Serial.print(dataT.id); Serial.print(" - "); Serial.println(dataT.ans);
  radio.write(&dataT, sizeof(dataT)); // émission du message via nRF24L01
  radio.startListening();
}

void play(void)
{
  int16_t answer;
  switch (play_time) {
    case false:
      stop_led();
      break;

    case true:
      snake_led();
      stop_led();
      //int16_t b = 9;
      //send_value(b);
      if (!digitalRead(button_blue)) {answer = 1; play_time = false; send_value(answer);}
      else if (!digitalRead(button_green)) {answer = 2; play_time = false; send_value(answer);}
      else if (!digitalRead(button_yellow)) {answer = 3; play_time = false; send_value(answer);}
      else if (!digitalRead(button_red)) {answer = 4; play_time = false; send_value(answer);}
      break;
  }
}

void init_led_button(void)
{
  FastLED.addLeds<NEOPIXEL, DATA_PIN>(leds, NUM_LEDS);  // GRB ordering is assumed
  stop_led();
  pinMode(button_blue, INPUT_PULLUP);
  pinMode(button_green, INPUT_PULLUP);
  pinMode(button_yellow, INPUT_PULLUP);
  pinMode(button_red, INPUT_PULLUP);
  pinMode(button_middle, INPUT_PULLUP);
}

void stop_led(void)
{
  for(int i=0;i<5;i++){
    leds[i] = CRGB::Black;
  }
  FastLED.show();
}

void snake_led(void)
{
  leds[0] = CRGB::Green;
  leds[4] = CRGB::Black;
  FastLED.show();
  delay(100);
  leds[1] = CRGB::Yellow;
  leds[0] = CRGB::Black;
  FastLED.show();
  delay(100);
  leds[3] = CRGB::Red;
  leds[1] = CRGB::Black;
  FastLED.show();
  delay(100);
  leds[4] = CRGB::Blue;
  leds[3] = CRGB::Black;
  FastLED.show();
  delay(100);
}

