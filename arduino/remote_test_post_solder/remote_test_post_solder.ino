/*******************************************************************
Remote : test solder
// Changelog

********************************************************************/

#include <FastLED.h>
#define NUM_LEDS 5
#define DATA_PIN 2

// Define the array of leds
CRGB leds[NUM_LEDS];

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
  
  init_led_button();
}

void loop(void){
  currentMillis = millis();
  //Serial.println(currentMillis);

  test();

  delay(10);
}

void test(void)
{
      //light_led();
      //delay(1000);
      stop_led();
      //int16_t b = 9;
      //send_value(b);
      if (!digitalRead(button_blue)) {for(int i=0;i<5;i++) leds[i] = CRGB::Blue; FastLED.show(); Serial.println("OK");delay(500);stop_led();}
      else if (!digitalRead(button_green)) {for(int i=0;i<5;i++) leds[i] = CRGB::Green; FastLED.show(); Serial.println("OK");delay(500);stop_led();}
      else if (!digitalRead(button_yellow))  {for(int i=0;i<5;i++) leds[i] = CRGB::Yellow; FastLED.show(); Serial.println("OK");delay(500);stop_led();}
      else if (!digitalRead(button_red))  {for(int i=0;i<5;i++) leds[i] = CRGB::Red; FastLED.show();Serial.println("OK"); delay(500);stop_led();}
      else if (!digitalRead(button_middle))  {for(int i=0;i<5;i++) leds[i] = CRGB::White; FastLED.show(); Serial.println("OK");delay(500);stop_led();}

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

void light_led(void)
{
  leds[4] = CRGB::Blue;
  leds[0] = CRGB::Green;
  leds[3] = CRGB::Yellow;
  leds[1] = CRGB::Red;
  leds[2] = CRGB::White;
  FastLED.show();
  delay(10);
}

