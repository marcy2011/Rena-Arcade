      document.addEventListener("contextmenu", function (e) {
        e.preventDefault();
        if (window.innerWidth > 768) {
          var menu = document.getElementById("custom-menu");
          menu.style.display = "block";
          menu.style.left = e.pageX + "px";
          menu.style.top = e.pageY + "px";
        }
      });

      document.addEventListener("click", function (event) {
        var menu = document.getElementById("custom-menu");
        if (
          menu &&
          (!menu.contains(event.target) || event.target.tagName === "A")
        ) {
          menu.style.display = "none";
        }
      });

      let isVerifyingToken = false;
      let tokenVerificationInterval = null;

      document.addEventListener("DOMContentLoaded", function () {
        console.log("DOM completamente caricato");
        loadSavedLanguage();
        handleTokenFromURL();
        setupEventListeners();
        checkLoginStatus();
      });

      function setupEventListeners() {
        console.log("Impostazione event listeners");

        const profileButton = document.getElementById("profile-button");
        const profileMenu = document.getElementById("profile-menu");

        if (profileButton && profileMenu) {
          profileButton.addEventListener("click", function (e) {
            e.stopPropagation();
            profileMenu.classList.toggle("active");
          });

          document.addEventListener("click", function () {
            profileMenu.classList.remove("active");
          });

          profileMenu.addEventListener("click", function (e) {
            e.stopPropagation();
          });
        }

        const closePopup = document.getElementById("closeLoginPopup");
        const loginPopup = document.getElementById("loginPopup");

        if (closePopup && loginPopup) {
          closePopup.addEventListener("click", function () {
            loginPopup.classList.remove("active");
          });
        }

        const menuIcon = document.querySelector(".menu-icon");
        if (menuIcon && menuIcon.closest("a")) {
          menuIcon.closest("a").addEventListener("click", function (e) {
            e.preventDefault();
            window.location.href =
              "https://renaarcade.altervista.org/menu.html";
          });
        }
      }

      function openCrossDomainLogin() {
        console.log("Apertura login cross-domain");
        document.getElementById("loginPopup").classList.add("active");

        const loginUrl = `https://rena.altervista.org/login.php?external_domain=${encodeURIComponent(
          window.location.origin
        )}`;

        const loginWindow = window.open(
          loginUrl,
          "RenaLogin",
          "width=500,height=600,scrollbars=no,resizable=no"
        );

        if (!loginWindow) {
          document.getElementById("loginPopup").classList.remove("active");
          alert(
            "Il browser ha bloccato la popup. Per favore consenti le popup per questo sito."
          );
          return;
        }

        const checkWindowClosed = setInterval(() => {
          if (loginWindow.closed) {
            clearInterval(checkWindowClosed);
            document.getElementById("loginPopup").classList.remove("active");
            checkLoginStatus();
          }
        }, 500);

        setTimeout(() => {
          if (!loginWindow.closed) {
            loginWindow.close();
            document.getElementById("loginPopup").classList.remove("active");
            alert("Tempo scaduto. Riprova.");
          }
        }, 30000);
      }

      function handleTokenFromURL() {
        console.log("Controllo token dall'URL");
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get("token");
        const user_id = urlParams.get("user_id");
        const username = urlParams.get("username");

        if (token && user_id && username) {
          console.log("Token trovato nell'URL, salvataggio dati utente");
          const userData = {
            status: "success",
            user_id: user_id,
            username: username,
            token: token,
            lastVerification: Date.now(),
          };

          localStorage.setItem("user_data", JSON.stringify(userData));
          updateProfileUI(userData);
          startTokenVerification();
          window.history.replaceState(
            {},
            document.title,
            window.location.pathname
          );
        } else {
          checkLoginStatus();
        }
      }

      function checkLoginStatus() {
        console.log("Controllo stato login...");
        const savedUserData = localStorage.getItem("user_data");

        if (savedUserData) {
          try {
            const userData = JSON.parse(savedUserData);
            console.log("Dati utente trovati:", userData);

            updateProfileUI(userData);

            if (userData.token && !isVerifyingToken) {
              const now = Date.now();
              const lastVerification = userData.lastVerification || 0;

              if (now - lastVerification > 5000) {
                isVerifyingToken = true;
                console.log("Avvio verifica token dopo ritardo");

                setTimeout(() => {
                  verifyToken(userData.token)
                    .then((valid) => {
                      console.log("Risultato verifica token:", valid);
                      isVerifyingToken = false;

                      if (!valid) {
                        console.log(
                          "Token non valido, tentativo di refresh..."
                        );
                        refreshUserData();
                      } else {
                        userData.lastVerification = Date.now();
                        localStorage.setItem(
                          "user_data",
                          JSON.stringify(userData)
                        );
                      }
                    })
                    .catch((error) => {
                      console.error("Errore verifica token:", error);
                      isVerifyingToken = false;
                    });
                }, 1000);
              }
            }
          } catch (e) {
            console.error("Errore parsing dati utente:", e);
            localStorage.removeItem("user_data");
            showLoginUI();
          }
        } else {
          console.log("Nessun dato utente trovato");
          showLoginUI();
        }
      }

      window.addEventListener("message", function (event) {
        console.log(
          "Messaggio ricevuto:",
          event.data,
          "da origine:",
          event.origin
        );

        const allowedOrigins = [
          "https://rena.altervista.org",
          "https://renadeveloper.altervista.org",
        ];

        if (!allowedOrigins.includes(event.origin)) {
          console.log("Messaggio da origine non autorizzata:", event.origin);
          return;
        }

        if (event.data && event.data.status === "success") {
          console.log("Login successful, salvataggio dati utente");

          const userData = {
            status: "success",
            user_id: event.data.user_id,
            username: event.data.username,
            token: event.data.token,
            profile_photo: event.data.profile_photo,
            lastVerification: Date.now(),
          };

          localStorage.setItem("user_data", JSON.stringify(userData));

          updateProfileUI(userData);
          startTokenVerification();

          document.getElementById("loginPopup").classList.remove("active");
        }
      });

      function updateProfileUI(data) {
        console.log("Aggiornamento UI profilo con dati:", data);
        const profileButton = document.getElementById("profile-button");
        const accountLink = document.getElementById("account-link");

        if (!profileButton) {
          console.error("Pulsante profilo non trovato");
          return;
        }

        profileButton.innerHTML = "";

        if (
          data &&
          (data.logged_in === true || data.status === "success" || data.user_id)
        ) {
          const username = data.username || data.user_id || "Utente";

          if (data.profile_photo) {
            console.log(
              "Tentativo di caricamento foto profilo:",
              data.profile_photo
            );
            const img = document.createElement("img");
            img.className = "profile-pic";
            img.alt = "Foto profilo";
            img.src = data.profile_photo;

            img.onerror = function () {
              console.log(
                "Errore caricamento foto profilo, uso avatar di default"
              );
              this.remove();
              const defaultAvatar = document.createElement("div");
              defaultAvatar.className = "default-avatar";
              defaultAvatar.textContent = username.charAt(0).toUpperCase();
              profileButton.appendChild(defaultAvatar);
            };

            img.onload = function () {
              console.log("Foto profilo caricata con successo");
            };

            profileButton.appendChild(img);
          } else {
            console.log("Nessuna foto profilo, uso avatar di default");
            const defaultAvatar = document.createElement("div");
            defaultAvatar.className = "default-avatar";
            defaultAvatar.textContent = username.charAt(0).toUpperCase();
            profileButton.appendChild(defaultAvatar);
          }

          if (accountLink) {
            accountLink.href = `https://rena.altervista.org/account.php?user_id=${data.user_id}`;
            accountLink.onclick = null;
            accountLink.querySelector("span").textContent = "Il mio account";
            accountLink
              .querySelector("span")
              .setAttribute("data-translate-it", "Il mio account");
            accountLink
              .querySelector("span")
              .setAttribute("data-translate-en", "My Account");
          }

          console.log("UI profilo aggiornata per utente loggato");
        } else {
          console.log("UI profilo impostata per utente non loggato");
          const loginButton = document.createElement("div");
          loginButton.className = "default-avatar";
          loginButton.innerHTML = '<i class="fas fa-user"></i>';
          loginButton.onclick = openCrossDomainLogin;
          profileButton.appendChild(loginButton);

          if (accountLink) {
            accountLink.href = "#";
            accountLink.onclick = function (e) {
              e.preventDefault();
              openCrossDomainLogin();
            };
            accountLink.querySelector("span").textContent = "Accedi";
            accountLink
              .querySelector("span")
              .setAttribute("data-translate-it", "Accedi");
            accountLink
              .querySelector("span")
              .setAttribute("data-translate-en", "Login");
          }
        }
      }

      function showLoginUI() {
        console.log("Mostra UI login");
        const profileButton = document.getElementById("profile-button");
        const accountLink = document.getElementById("account-link");

        if (profileButton && accountLink) {
          profileButton.innerHTML =
            '<div class="default-avatar"><i class="fas fa-user"></i></div>';

          const span = accountLink.querySelector("span");
          if (span) {
            span.setAttribute("data-translate-it", "Accedi");
            span.setAttribute("data-translate-en", "Login");
            span.textContent = "Accedi";
          }

          accountLink.onclick = function (e) {
            e.preventDefault();
            openCrossDomainLogin();
          };

          accountLink.href = "#";
        }
      }

      async function verifyToken(token) {
        console.log("Verifica token in corso...");
        if (!token) return false;

        try {
          const response = await fetch(
            "https://rena.altervista.org/verify_token.php",
            {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({ token: token }),
            }
          );

          console.log("Status risposta:", response.status);
          console.log("Status text:", response.statusText);

          const result = await response.json();
          console.log("Risultato verifica completo:", result);

          return result.valid === true;
        } catch (error) {
          console.error("Errore durante la verifica del token:", error);
          return true;
        }
      }

      function refreshUserData() {
        console.log("Refresh dati utente");
        const savedUserData = localStorage.getItem("user_data");

        if (savedUserData) {
          try {
            const userData = JSON.parse(savedUserData);
            if (userData.token) {
              fetch("https://rena.altervista.org/get_user_data.php", {
                method: "POST",
                headers: {
                  "Content-Type": "application/json",
                },
                body: JSON.stringify({ token: userData.token }),
              })
                .then((response) => {
                  if (!response.ok) {
                    throw new Error("Network response was not ok");
                  }
                  return response.json();
                })
                .then((data) => {
                  if (data.status === "success") {
                    const updatedUserData = {
                      ...userData,
                      ...data,
                      lastVerification: Date.now(),
                    };
                    localStorage.setItem(
                      "user_data",
                      JSON.stringify(updatedUserData)
                    );
                    updateProfileUI(updatedUserData);
                  } else {
                    console.log("Refresh fallito, ma mantengo dati locali");
                  }
                })
                .catch((error) => {
                  console.error(
                    "Errore durante l'aggiornamento dei dati utente:",
                    error
                  );
                });
            }
          } catch (e) {
            console.error("Errore parsing dati utente:", e);
          }
        }
      }

      function startTokenVerification() {
        console.log("Avvio verifica periodica token");
        if (tokenVerificationInterval) {
          clearInterval(tokenVerificationInterval);
        }

        tokenVerificationInterval = setInterval(() => {
          const savedUserData = localStorage.getItem("user_data");
          if (savedUserData) {
            try {
              const userData = JSON.parse(savedUserData);
              if (userData.token && !isVerifyingToken) {
                isVerifyingToken = true;
                verifyToken(userData.token)
                  .then((valid) => {
                    isVerifyingToken = false;
                    if (!valid) {
                      console.log("Token non valido, disconnessione");
                      localStorage.removeItem("user_data");
                      showLoginUI();
                      clearInterval(tokenVerificationInterval);
                    }
                  })
                  .catch((error) => {
                    console.error("Errore verifica token:", error);
                    isVerifyingToken = false;
                  });
              }
            } catch (e) {
              console.error("Errore parsing dati utente:", e);
              localStorage.removeItem("user_data");
              showLoginUI();
              clearInterval(tokenVerificationInterval);
            }
          } else {
            clearInterval(tokenVerificationInterval);
          }
        }, 30000);
      }

      function loadSavedLanguage() {
        const savedLang = localStorage.getItem("selectedLanguage");
        if (savedLang) {
          translatePage(savedLang);
        }
      }

      function translatePage(lang) {
        console.log("Traduzione in:", lang);
        localStorage.setItem("selectedLanguage", lang);

        const languageOptions = document.querySelectorAll(".language-option");
        languageOptions.forEach((option) => {
          if (option.getAttribute("data-lang") === lang) {
            option.classList.add("active");
          } else {
            option.classList.remove("active");
          }
        });

        const elementsToTranslate = document.querySelectorAll(
          "[data-translate-it], [data-translate-en]"
        );
        elementsToTranslate.forEach((element) => {
          if (lang === "it") {
            element.textContent = element.getAttribute("data-translate-it");
          } else if (lang === "en") {
            element.textContent = element.getAttribute("data-translate-en");
          }
        });

        const placeholdersToTranslate = document.querySelectorAll(
          "[data-placeholder-it], [data-placeholder-en]"
        );
        placeholdersToTranslate.forEach((element) => {
          if (lang === "it") {
            element.placeholder = element.getAttribute("data-placeholder-it");
          } else if (lang === "en") {
            element.placeholder = element.getAttribute("data-placeholder-en");
          }
        });
        const pageTitle = document.getElementById("page-title");
        if (pageTitle) {
          if (lang === "en") {
            pageTitle.textContent = "Rena Arcades - {GAME_NAME_IT}";
          } else {
            pageTitle.textContent = "Rena Arcades - {GAME_NAME_EN}";
          }
        }
      }

      const searchInput = document.querySelector(".search-input");
      const appCards = document.querySelectorAll(".app-card");
      const noResults = document.querySelector(".no-results");

      searchInput.addEventListener("input", function () {
        const searchTerm = this.value.toLowerCase();
        let hasResults = false;

        appCards.forEach((card) => {
          const appName = card
            .querySelector(".app-name")
            .textContent.toLowerCase();
          if (appName.includes(searchTerm)) {
            card.style.display = "flex";
            hasResults = true;
          } else {
            card.style.display = "none";
          }
        });

        if (hasResults) {
          noResults.style.display = "none";
        } else {
          noResults.style.display = "block";
        }
      });
