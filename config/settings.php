<?php
// config/settings.php  — all store settings in one place
define('SITE_NAME', 'Mans Fashion');
define('SITE_TAGLINE', 'Premium menswear, delivered across Bangladesh');
define('CONTACT_PHONE', '+8801860035337');          // shown on site
define('CONTACT_PHONE_DISPLAY', '+880 1860-035337');
define('WHATSAPP_NUMBER', '8801860035337');           // no + sign
define('STORE_HOURS', '10 AM – 10 PM, every day');
define('MADE_BY', 'SAS');

// Shipping (in Taka)
define('FREE_SHIPPING_MIN', 2000);   // free delivery at or above this subtotal
define('SHIPPING_INSIDE', 60);       // inside Chattogram
define('SHIPPING_OUTSIDE', 120);     // rest of Bangladesh
define('HOME_ZONE_LABEL', 'Chattogram');

// Mobile payment (customer sends money, then enters TrxID at checkout)
define('BKASH_NUMBER', '01860035337');
define('NAGAD_NUMBER', '01860035337');

// Admin panel password — CHANGE THIS before going live
define('ADMIN_PASSWORD', 'ChangeMe@2026');

// ---- Chat assistant brain ----
// With an API key the assistant understands Bangla/English/Banglish, answers ANY question,
// and always reads the live database (products, prices, stock, offers, reviews, your notes).
// Free key: https://console.groq.com/keys   -> set AI_PROVIDER to 'groq' and paste the key below.
// (OpenAI: AI_PROVIDER 'openai', AI_MODEL 'gpt-4o-mini'.)
// (Google Gemini: AI_PROVIDER 'gemini', key from https://aistudio.google.com/apikey, AI_MODEL 'gemini-flash-latest'.)
// (xAI Grok, key starts with xai-: AI_PROVIDER 'xai'.)
// Leave AI_API_KEY empty to use the built-in English-only assistant (shop topics only).
define('AI_PROVIDER', 'groq');                 // 'groq' | 'openai' | 'gemini' | 'xai' | ''
define('AI_API_KEY', '');                      // <-- paste your key here
define('AI_MODEL', 'openai/gpt-oss-120b');     // Groq model shown in your Playground. Use 'auto' to let the site pick one.

