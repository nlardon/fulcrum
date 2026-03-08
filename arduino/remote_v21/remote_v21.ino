/*******************************************************************
Remote : controller ID + Colors
// Changelog
v21 - add pipe

********************************************************************/

#include <SPI.h>
#include "nRF24L01.h"
#include "RF24.h"
#include <FastLED.h>
#define NUM_LEDS 5
#define DATA_PIN 2
int16_t num_remote = 1;
int16_t num_remote_pipe = 5;

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
//Create up to 6 pipe addresses P0 - P5;  the "LL" is for LongLong type
const uint64_t rAddress[] = {0x7878787878LL, 0xB3B4B5B6F1LL, 0xB3B4B5B6CDLL, 0xB3B4B5B6A3LL, 0xB3B4B5B60FLL, 0xB3B4B5B605LL };

//const uint64_t addresseT = 0x1111111111;
//const uint64_t addresseR = 0x1111111000;

bool play_time = false;

int button_blue = 4;
int button_green = 5;
int button_yellow = 7;
int button_red = 6;
int button_middle = 8;

unsigned long currentMillis = 0;    // stores the value of millis() in each iteration of loop()
unsigned long previousMillis = 0;
int led = 0;

void setup(void)
{
  Serial.begin(115200);
  Serial.println("Emetteur de donnees");
  radio.begin();  //Start the nRF24 module
  radio.setPALevel(RF24_PA_LOW);  // "short range setting" - increase if you want more range AND have a good power supply
  radio.setDataRate(RF24_250KBPS); // Lower rate for better range/reliability
  radio.setChannel(108);          // the higher channels tend to be more "open"
  radio.setAutoAck(false);
  // Open up to six pipes for PRX to receive data
  radio.openReadingPipe(0,rAddress[0]);
  //radio.openReadingPipe(1,rAddress[1]);
  //radio.openReadingPipe(2,rAddress[2]);
  //radio.openReadingPipe(3,rAddress[3]);
  //radio.openReadingPipe(4,rAddress[4]);
  //radio.openReadingPipe(5,rAddress[5]);
  radio.startListening();                 // Start listening for messagesradio.begin();
 
  init_led_button();
  //dataR.ans="p";
}

void loop(void){
  currentMillis = millis();
  //Serial.println(currentMillis);
  while ( radio.available() ){
    radio.read( &dataR,  sizeof(dataR) );
    Serial.print("Message recu : "); Serial.print(dataR.id); Serial.print(" - "); Serial.println(dataR.ans); 
  }
  //play_time = true; //to debug
  //dataR.ans = 1111;
  if(dataR.ans==1111){
    play_time = true;
    dataR.ans=0;
    previousMillis = millis();
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
  radio.openWritingPipe(rAddress[num_remote_pipe]); //Open writing pipe to the nRF24 that guessed the right number
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
      //snake_led();
      light_led();
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
  Serial.println(previousMillis);
  if(currentMillis - previousMillis < 100) {  
    previousMillis += 100;
    switch (led){
      case 4:
        leds[4] = CRGB::Blue;
        leds[3] = CRGB::Black;
        led = 0;
        break;
      case 0:
        leds[0] = CRGB::Green;
        leds[4] = CRGB::Black;
        led = 1;
        break;
      case 3:
        leds[3] = CRGB::Yellow;
        leds[1] = CRGB::Black;
        led = 4;
        break;
      case 1:
        leds[1] = CRGB::Red;
        leds[0] = CRGB::Black;
        led = 3;
        break;
    }
    FastLED.show();
  }
}

void light_led(void)
{
  leds[4] = CRGB::Blue;
  leds[0] = CRGB::Green;
  leds[3] = CRGB::Yellow;
  leds[1] = CRGB::Red;
  FastLED.show();
  delay(10);
}

