
function pureFadeIn(elem, display){
  var el = document.getElementById(elem);
  el.style.opacity = 0;
  el.style.display = display || "block";

  (function fade() {
    var val = parseFloat(el.style.opacity);
    if (!((val += .02) > 1)) {
      el.style.opacity = val;
      requestAnimationFrame(fade);
    }
  })();
};
function pureFadeOut(elem){
  var el = document.getElementById(elem);
  el.style.opacity = 1;

  (function fade() {
    if ((el.style.opacity -= .02) < 0) {
      el.style.display = "none";
    } else {
      requestAnimationFrame(fade);
    }
  })();
};

function cookieConsent() {
	document.body.innerHTML += '<div class="cookieConsentContainer" id="cookieConsentContainer"><div class="cookieTitle"><a>Wir sind live</a></div><div class="cookieButton"><a onClick="purecookieDismiss();">zu YouTube</a></div></div>';
	pureFadeIn("cookieConsentContainer");
}

function purecookieDismiss() {
  pureFadeOut("cookieConsentContainer");
  window.open(youtube_link);
}

window.onload = function() { cookieConsent(); };