<?php
// app/cookie_consent.php
function cookie_prefs_get(): array {
  $raw = $_COOKIE['cookie_prefs'] ?? '';
  if (!$raw) return [];
  $arr = json_decode($raw, true);
  return is_array($arr) ? $arr : [];
}
function cookie_has_consent(string $category): bool {
  $prefs = cookie_prefs_get();
  if (!$prefs) return false;
  if ($category === 'necessary') return true;
  return !empty($prefs[$category]);
}
function cookie_consent_js_guard(): void {
  echo '<script>window.CookiePrefs=(function(){try{return JSON.parse(document.cookie.split("; ").find(function(x){return x.indexOf("cookie_prefs=")===0;})?.split("=")[1]||"{}")}catch(e){return {}}})();</script>';
}
