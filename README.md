# Rena Arcades
Rena Arcades é un servizio appartenente a Rena che ti permette di giocare ai tuoi giochi preferiti senza download

I file allegati qui su Git Hub sono solo quelli principali

# Vedi tutto il sito su https://renaarcade.altervista.org
# Scarica l'app su https://renaarcade.altervista.org/download.html o su https://renastore.altervista.org/rena-arcades.html
<a href="https://renastore.altervista.org/rena-arcades.html">
    <img src="https://renadeveloper.altervista.org/downloadrs.png" alt="Download On The Rena Store" width="200">
</a>

  <p id="testo">Ciao, come stai?</p>
  <img src="https://renadeveloper.altervista.org/bandieraen.png" alt="Traduci in inglese" 
       style="cursor: pointer;" 
       onclick="traduci(); return false;">

  <script>
    function traduci() {
      const testi = {
        "Ciao, come stai?": "Hello, how are you?"
      };
      document.querySelectorAll("#testo").forEach(el => {
        el.textContent = testi[el.textContent] || el.textContent;
      });
    }
  </script>
