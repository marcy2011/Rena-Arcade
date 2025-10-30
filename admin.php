<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

$games_file = 'games.json';
$games = [];
if (file_exists($games_file)) {
    $games = json_decode(file_get_contents($games_file), true) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_game'])) {
    $filename = generateGameFilename($_POST['game_name_it']);
    
    $new_game = [
        'id' => uniqid(),
        'name' => $_POST['game_name'],
        'name_it' => $_POST['game_name_it'],
        'name_en' => $_POST['game_name_en'],
        'image' => $_POST['game_image'],
        'page_url' => $_POST['game_page_url'],
        'filename' => $filename,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $games[] = $new_game;
    file_put_contents($games_file, json_encode($games, JSON_PRETTY_PRINT));
    
    generateGamePage($new_game);
    
    updateGamesPage($games);
    
    header('Location: admin.php?success=1');
    exit;
}

if (isset($_GET['delete'])) {
    $game_id = $_GET['delete'];
    $games = array_filter($games, function($game) use ($game_id) {
        return $game['id'] !== $game_id;
    });
    $games = array_values($games);
    file_put_contents($games_file, json_encode($games, JSON_PRETTY_PRINT));
    
    updateGamesPage($games);
    
    header('Location: admin.php?deleted=1');
    exit;
}

function generateGameFilename($gameName) {
    $filename = preg_replace('/[^a-zA-Z0-9\s]/', '', $gameName);
    $filename = str_replace(' ', '-', $filename);
    $filename = strtolower($filename);
    return $filename . '.html';
}

function generateGamePage($game) {
    $game_page_template = getGameTemplate();
    
    $page_content = str_replace(
        [
            '{GAME_NAME_IT}',
            '{GAME_NAME_EN}',
            '{GAME_DESCRIPTION_IT}',
            '{GAME_DESCRIPTION_EN}',
            '{GAME_IFRAME_URL}',
            '{NEW_TAB_BUTTON}'
        ],
        [
            htmlspecialchars($game['name_it']),
            htmlspecialchars($game['name_en']),
            "Gioca a " . htmlspecialchars($game['name_it']) . " gratuitamente su Rena Arcades. Divertiti con questo fantastico gioco online!",
            "Play " . htmlspecialchars($game['name_en']) . " for free on Rena Arcades. Enjoy this amazing online game!",
            $game['page_url'],
            '<div class="open-new-tab"><a href="' . $game['page_url'] . '" target="_blank" title="Apri in nuova scheda"><svg class="svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M20 4L12 12M20 4V8.5M20 4H15.5M19 12.5V16.8C19 17.9201 19 18.4802 18.782 18.908C18.5903 19.2843 18.2843 19.5903 17.908 19.782C17.4802 20 16.9201 20 15.8 20H7.2C6.0799 20 5.51984 20 5.09202 19.782C4.71569 19.5903 4.40973 19.2843 4.21799 18.908C4 18.4802 4 17.9201 4 16.8V8.2C4 7.0799 4 6.51984 4.21799 6.09202C4.40973 5.71569 4.71569 5.40973 5.09202 5.21799C5.51984 5 6.07989 5 7.2 5H11.5" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg></a></div>'
        ],
        $game_page_template
    );
    
    file_put_contents($game['filename'], $page_content);
}

function updateGamesPage($games) {
    $giochi_content = file_get_contents('giochi.html');
    
    if (empty($games)) {
        return false;
    }
    
    $last_game = end($games);
    
    if (empty($last_game['filename']) || empty($last_game['image']) || empty($last_game['name_it'])) {
        error_log("Dati del gioco mancanti: " . print_r($last_game, true));
        return false;
    }
    
    $new_game_html = '
            <div class="app-card">
                <a href="' . $last_game['filename'] . '" target="_blank"
                    style="text-decoration: none;">
                    <img src="' . $last_game['image'] . '" alt="' . htmlspecialchars($last_game['name_it']) . ' Logo"
                        class="app-icon">
                    <p class="app-name">' . htmlspecialchars($last_game['name_it']) . '</p>
                </a>
            </div>';

    $last_app_card_pos = strrpos($giochi_content, '<div class="app-card">');
    
    if ($last_app_card_pos !== false) {
        $temp_content = substr($giochi_content, $last_app_card_pos);
        $closing_div_pos = strpos($temp_content, '</div>');
        
        if ($closing_div_pos !== false) {
            $insert_pos = $last_app_card_pos + $closing_div_pos + 6; 
            
            $giochi_content = substr_replace($giochi_content, "\n" . $new_game_html, $insert_pos, 0);
            file_put_contents('giochi.html', $giochi_content);
            return true;
        }
    }
    
    return false;
}

function getGameTemplate() {
    return '<!DOCTYPE html>
<html lang="it">
  <head>
    <title id="page-title">Rena Arcades</title>
    <link rel="icon" type="image/x-icon" href="logo.png" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta charset="UTF-8" />
    <meta property="og:url" content="renaarcade.altervista.org" />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap"
    />
    <link
      href="https://api.fontshare.com/v2/css?f[]=open-sauce-one@400&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />

    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        background: black;
        color: white;
        font-family: "Open Sauce One", sans-serif;
        min-height: 100vh;
        cursor: url("cursore.png"), auto;
      }

      main {
        padding-top: 150px !important;
      }

      .floating-navbar {
        position: fixed;
        top: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50px;
        padding: 15px 40px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 90%;
        max-width: 1000px;
        z-index: 1000;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
      }

      .floating-navbar:hover {
        background: rgba(0, 0, 0, 0.4);
        transform: translateX(-50%) translateY(-2px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
      }

      .header-brand {
        text-decoration: none;
      }

      .header-brand:hover {
        transform: none;
      }

      .navbar-nav {
        display: flex;
        align-items: center;
        gap: 30px;
        flex: 1;
        justify-content: center;
      }

      .nav-link {
        color: white;
        text-decoration: none;
        font-size: 16px;
        font-weight: 500;
        padding: 8px 16px;
        border-radius: 20px;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        line-height: 1;
      }

      .nav-link::before {
        display: none;
      }

      .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-2px);
      }

      .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        box-shadow: 0 2px 8px rgba(255, 255, 255, 0.2);
      }

      .navbar-account {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-shrink: 0;
      }

      .account-menu {
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: none;
      }

      .menu-icon {
        width: 28px;
        height: 28px;
        transition: none;
        display: block;
      }

      .menu-icon path {
        transition: none;
      }

      .profile-menu-container {
        position: relative;
        z-index: 200;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      #profile-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
      }

      .profile-pic {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .profile-pic:hover {
        transform: scale(1.1);
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
      }

      .default-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        font-weight: 600;
        border: 2px solid rgba(255, 255, 255, 0.3);
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .profile-menu {
        display: none;
        position: absolute;
        top: 65px;
        right: -15px;
        background: rgba(0, 0, 0, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        padding: 15px;
        width: 200px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 1000;
        animation: fadeIn 0.3s ease;
      }

      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }

        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .profile-menu.active {
        display: block;
        z-index: 1001;
      }

      .profile-menu-item {
        display: flex;
        align-items: center;
        padding: 10px;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
        margin-bottom: 5px;
      }

      .profile-menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
      }

      .profile-menu-item i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
      }

      .profile-menu-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 10px 0;
      }

      .language-option {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
      }

      .language-option:hover {
        background: rgba(255, 255, 255, 0.1);
      }

      .language-option.active {
        background: rgba(255, 255, 255, 0.15);
      }

      .language-content {
        display: flex;
        align-items: center;
      }

      .language-flag {
        width: 20px;
        height: 15px;
        margin-right: 10px;
        border-radius: 3px;
      }

      .check-icon {
        width: 16px;
        height: 16px;
        stroke: #4ade80;
        stroke-width: 2;
        opacity: 0;
        transition: opacity 0.3s ease;
      }

      .language-option.active .check-icon {
        opacity: 1;
      }

      @media screen and (max-width: 768px) {
        .floating-navbar {
          width: 95%;
          padding: 12px 20px;
          top: 20px;
          justify-content: space-between;
        }

        .profile-menu-container {
          order: 1;
          flex-grow: 1;
          display: flex;
          justify-content: flex-start;
          margin-left: -7px;
        }

        .navbar-logo {
          order: 2;
          position: static;
          left: auto;
          transform: none;
          flex-shrink: 0;
          display: flex;
          justify-content: center;
          align-items: center;
          flex-grow: 0;
          height: 100%;
        }

        .navbar-nav {
          display: none;
        }

        .navbar-account {
          order: 3;
          display: flex;
          align-items: center;
          gap: 10px;
          margin-left: 0;
          flex-grow: 1;
          justify-content: flex-end;
        }

        .menu-icon {
          width: 28px;
          height: 28px;
          transform: translateY(-1px) scaleX(-1);
        }

        .profile-menu {
          left: 0 !important;
          right: auto !important;
          transform: none !important;
        }
      }

      @media screen and (min-width: 769px) {
        .floating-navbar {
          top: 30px;
          max-width: 1100px;
        }

        .navbar-nav {
          display: flex;
          order: 2;
        }

        .navbar-logo {
          order: 1;
        }

        .profile-menu-container {
          order: 3;
        }

        .navbar-account {
          display: none;
        }
      }

      @media screen and (max-width: 480px) {
        .floating-navbar {
          padding: 8px 16px;
        }

        .menu-icon {
          width: 24px;
          height: 24px;
        }
      }

      #custom-menu {
        display: none;
        position: absolute;
        background: rgba(0, 0, 0, 0.9);
        border-radius: 15px;
        padding: 15px;
        backdrop-filter: blur(20px);
        z-index: 99999999;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      }

      #custom-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
      }

      #custom-menu ul li {
        margin-bottom: 10px;
      }

      #custom-menu ul li a {
        display: flex;
        align-items: center;
        color: #fff;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
      }

      #custom-menu ul li a:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
      }

      #custom-menu ul li a img {
        margin-right: 10px;
        border-radius: 5px;
      }

      #custom-menu ul li a span {
        font-size: 16px;
      }

      #custom-menu ul li a.has-svg span {
        margin-left: 10px;
      }

      .custom-menu-svg {
        width: 22px;
        height: 22px;
        max-width: 100%;
        max-height: 100%;
      }

      main {
        padding-top: 120px;
      }

      @media (max-width: 767px) {
        #custom-menu {
          display: none !important;
        }
      }

      .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
      }

      ::selection {
        background-color: white;
        color: black;
      }

      *:focus {
        outline: none;
      }

      * {
        -webkit-tap-highlight-color: transparent;
      }

      .login-popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
      }

      .login-popup.active {
        opacity: 1;
        visibility: visible;
      }

      .login-popup-content {
        background: rgba(0, 0, 0, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 30px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        position: relative;
      }

      .close-popup {
        position: absolute;
        top: 15px;
        right: 15px;
        color: rgba(255, 255, 255, 0.6);
        font-size: 22px;
        cursor: pointer;
        transition: color 0.2s ease;
      }

      .close-popup:hover {
        color: white;
      }

      .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        border-top: 4px solid #fff;
        animation: spin 1s linear infinite;
      }

      @keyframes spin {
        0% {
          transform: rotate(0deg);
        }

        100% {
          transform: rotate(360deg);
        }
      }

      .context-menu-svg {
        width: 22px !important;
        height: 22px !important;
        margin-right: 10px;
      }

      .navbar-logo {
        position: relative;
      }

      .navbar-logo .header-brand {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: block;
      }

      .navbar-logo-img {
        height: 55px;
        display: block;
        border-radius: 30px;
      }

      @media screen and (min-width: 769px) {
        .navbar-logo-img {
          margin-left: 30px;
        }
      }

      body,
      html {
        margin: 0;
        padding: 0;
        font-family: "Helvetica Neue", Arial, sans-serif;
        background-color: black;
        color: white;
      }

      .game-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
      }

      .page-title {
        text-align: center;
        margin-bottom: 2rem;
        font-size: 2.5rem;
        font-weight: 600;
      }

      .page-subtitle {
        text-align: center;
        margin-bottom: 3rem;
        font-size: 1.1rem;
        color: rgba(255, 255, 255, 0.7);
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
      }

      .game-frame-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2rem;
      }

      .game-frame {
        width: 100%;
        height: 600px;
        border: none;
        border-radius: 20px;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
        overflow: hidden;
        background: black;
      }

      .game-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
      }

      .action-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
      }

      .action-button:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
      }

      .action-button i {
        font-size: 1.1rem;
      }

      .open-new-tab {
        position: absolute;
        bottom: 20px;
        right: 20px;
        z-index: 10;
      }

      .open-new-tab a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background: rgba(0, 0, 0, 0.7);
        border-radius: 50%;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
      }

      .open-new-tab a:hover {
        background: rgba(0, 0, 0, 0.9);
        transform: scale(1.1);
      }

      .open-new-tab img {
        width: 24px;
        height: 24px;
        border-radius: 5px;
      }

      @media screen and (max-width: 768px) {
        .game-frame {
          height: 400px;
        }

        .page-title {
          font-size: 2rem;
        }

        .action-button {
          padding: 10px 20px;
          font-size: 0.9rem;
        }
      }

      @media screen and (max-width: 480px) {
        .game-frame {
          height: 300px;
        }

        .page-title {
          font-size: 1.8rem;
        }

        .game-actions {
          flex-direction: column;
          width: 100%;
        }

        .action-button {
          width: 100%;
          justify-content: center;
        }
      }
      
  .svg-icon{ width:32px; height:32px; display:block; }
    </style>
  </head>

  <body>
    <nav class="floating-navbar">
      <div class="profile-menu-container">
        <div id="profile-button"></div>
        <div class="profile-menu" id="profile-menu">
          <a
            href="https://rena.altervista.org/account.php"
            class="profile-menu-item"
            id="account-link"
          >
            <i class="fas fa-user"></i>
            <span
              data-translate-it="Il mio account"
              data-translate-en="My Account"
              >Il mio account</span
            >
          </a>
          <div class="profile-menu-divider"></div>
          <div
            class="profile-menu-item"
            style="pointer-events: none; opacity: 0.8"
          >
            <i class="fas fa-language"></i>
            <span data-translate-it="Lingua" data-translate-en="Language"
              >Lingua</span
            >
          </div>
          <div
            class="language-option"
            data-lang="it"
            onclick="translatePage(\'it\')"
          >
            <div class="language-content">
              <img
                src="https://renadeveloper.altervista.org/bandierait.png"
                class="language-flag"
                alt="Italiano"
              />
              <span>Italiano</span>
            </div>
            <svg
              class="check-icon"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 13l4 4L19 7"
              ></path>
            </svg>
          </div>
          <div
            class="language-option"
            data-lang="en"
            onclick="translatePage(\'en\')"
          >
            <div class="language-content">
              <img
                src="https://renadeveloper.altervista.org/bandieraen.png"
                class="language-flag"
                alt="English"
              />
              <span>English</span>
            </div>
            <svg
              class="check-icon"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 13l4 4L19 7"
              ></path>
            </svg>
          </div>
        </div>
      </div>

      <div class="navbar-logo">
        <a class="header-brand" href="https://renaarcade.altervista.org">
          <img
            src="logo-ra-transparent.png"
            alt="Rena Arcades"
            class="navbar-logo-img"
          />
        </a>
      </div>

      <div class="navbar-nav">
        <a href="https://renaarcade.altervista.org" class="nav-link">
          <img
            src="https://gcsapp.altervista.org/homebanner.png"
            alt="Home"
            width="19"
          />
          <span>Home</span>
        </a>
        <a
          href="https://renaarcade.altervista.org/giochi.html"
          class="nav-link"
        >
          <img
            src="https://gcsapp.altervista.org/giochibanner.png"
            alt="Giochi"
            width="20"
          />
          <span data-translate-it="Giochi" data-translate-en="Games"
            >Giochi</span
          >
        </a>
        <a
          href="https://renaarcade.altervista.org/download.html"
          class="nav-link"
        >
          <img src="downloadbanner.png" alt="Download" width="22" />
          <span>Download</span>
        </a>
      </div>

      <div class="navbar-account">
        <div class="account-menu">
          <a href="https://renaarcade.altervista.org/menu.html">
            <svg
              class="menu-icon"
              viewBox="0 0 24 24"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
              transform="rotate(0)matrix(-1, 0, 0, 1, 0, 0)"
              stroke="#ffffff"
            >
              <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
              <g
                id="SVGRepo_tracerCarrier"
                stroke-linecap="round"
                stroke-linejoin="round"
              ></g>
              <g id="SVGRepo_iconCarrier">
                <path
                  d="M4 6H20M4 12H14M4 18H9"
                  stroke="#ffffff"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                ></path>
              </g>
            </svg>
          </a>
        </div>
      </div>
    </nav>

    <div id="custom-menu">
      <ul>
        <li>
          <a href="https://renaarcade.altervista.org"
            ><img
              src="https://gcsapp.altervista.org/homebanner.png"
              alt="Image 1"
              width="20"
            /><span>Home</span></a
          >
        </li>
        <li>
          <a href="https://renaarcade.altervista.org/giochi.html"
            ><img
              src="https://gcsapp.altervista.org/giochibanner.png"
              alt="Image 3"
              width="20"
            /><span data-translate-it="Giochi" data-translate-en="Games"
              >Giochi</span
            ></a
          >
        </li>
        <li>
          <a href="https://renaarcade.altervista.org/download.html"
            ><img src="downloadbanner.png" alt="Image 5" width="20" /><span
              >Download</span
            ></a
          >
        </li>
      </ul>
    </div>

    <div class="login-popup" id="loginPopup">
      <div class="login-popup-content">
        <span class="close-popup" id="closeLoginPopup">&times;</span>
        <h3
          data-translate-it="Accesso in corso..."
          data-translate-en="Logging in..."
        >
          Accesso in corso...
        </h3>
        <p
          data-translate-it="Stai per essere reindirizzato alla pagina di login di Rena ID"
          data-translate-en="You will be redirected to Rena ID login page"
        >
          Stai per essere reindirizzato alla pagina di login di Rena ID
        </p>
        <div style="text-align: center; margin-top: 20px">
          <div class="spinner" style="margin: 0 auto"></div>
        </div>
      </div>
    </div>

    <main class="game-container">
      <h1 class="page-title" id="game-title">{GAME_NAME}</h1>

      <div class="game-frame-container">
        <div style="position: relative; width: 100%">
          <iframe
            id="game-frame"
            class="game-frame"
            src="{GAME_IFRAME_URL}"
            allowfullscreen
          ></iframe>
          <div class="open-new-tab">
            <a href="#" onclick="toggleFullscreen(); return false;">
              <svg class="svg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M20 4L12 12M20 4V8.5M20 4H15.5M19 12.5V16.8C19 17.9201 19 18.4802 18.782 18.908C18.5903 19.2843 18.2843 19.5903 17.908 19.782C17.4802 20 16.9201 20 15.8 20H7.2C6.0799 20 5.51984 20 5.09202 19.782C4.71569 19.5903 4.40973 19.2843 4.21799 18.908C4 18.4802 4 17.9201 4 16.8V8.2C4 7.0799 4 6.51984 4.21799 6.09202C4.40973 5.71569 4.71569 5.40973 5.09202 5.21799C5.51984 5 6.07989 5 7.2 5H11.5" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
            </a>
          </div>
        </div>

        <div class="game-actions">
          <a
            href="https://renaarcade.altervista.org/giochi.html"
            class="action-button"
          >
            <i class="fas fa-gamepad"></i>
            <span
              data-translate-it="Altri giochi"
              data-translate-en="More games"
              >Altri giochi</span
            >
          </a>
        </div>
      </div>
    </main>
    
    <script>
      function setupGame(gameData) {
        document.getElementById(\'game-title\').textContent = gameData.name;
        document.getElementById(\'game-title\').setAttribute(\'data-name-it\', gameData.name_it);
        document.getElementById(\'game-title\').setAttribute(\'data-name-en\', gameData.name_en);
        
        document.getElementById(\'game-description\').textContent = gameData.description_it;
        document.getElementById(\'game-description\').setAttribute(\'data-description-it\', gameData.description_it);
        document.getElementById(\'game-description\').setAttribute(\'data-description-en\', gameData.description_en);
        
        document.getElementById(\'game-frame\').src = gameData.iframe_url;
        document.getElementById(\'new-tab-link\').href = gameData.page_url;
        
        document.getElementById(\'page-title\').textContent = "Rena Arcades - " + gameData.name_it;
      }

      const gameData = {
        name_it: "{GAME_NAME_IT}",
        name_en: "{GAME_NAME_EN}", 
        description_it: "{GAME_DESCRIPTION_IT}",
        description_en: "{GAME_DESCRIPTION_EN}",
        iframe_url: "{GAME_IFRAME_URL}",
        page_url: "{GAME_PAGE_URL}"
      };

      document.addEventListener("DOMContentLoaded", function () {
        setupGame(gameData);
        translatePage("it");
      });

      function toggleFullscreen() {
        const iframe = document.getElementById("game-frame");
        
        if (iframe.requestFullscreen) {
          iframe.requestFullscreen();
        } else if (iframe.webkitRequestFullscreen) {
          iframe.webkitRequestFullscreen();
        } else if (iframe.mozRequestFullScreen) {
          iframe.mozRequestFullScreen();
        } else if (iframe.msRequestFullscreen) {
          iframe.msRequestFullscreen();
        }
      }

      ' . file_get_contents('game_template.js') . '
    </script>
  </body>
</html>';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Admin - Rena Arcades</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Open Sans', sans-serif; background: #0a0a0a; color: white; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { text-align: center; margin-bottom: 30px; color: white; }
        .admin-section { background: rgba(255, 255, 255, 0.05); border-radius: 15px; padding: 25px; margin-bottom: 30px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input, textarea, select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.2); background: rgba(0, 0, 0, 0.5); color: white; font-size: 16px; }
        .btn { background: #4a90e2; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        .btn:hover { background: #3a7bc8; }
        .games-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .game-card { background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 15px; text-align: center; border: 1px solid rgba(255, 255, 255, 0.1); }
        .game-image { width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .game-name { font-weight: 500; margin-bottom: 10px; }
        .delete-btn { background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: rgba(46, 204, 113, 0.2); border: 1px solid rgba(46, 204, 113, 0.5); }
        .file-info { background: rgba(255, 255, 255, 0.05); padding: 10px; border-radius: 6px; margin-top: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin - Gestione Giochi</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Gioco aggiunto con successo!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Gioco eliminato con successo!</div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2>Aggiungi Nuovo Gioco</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="game_name_it">Nome Gioco (Italiano)</label>
                    <input type="text" id="game_name_it" name="game_name_it" required>
                </div>
                
                <div class="form-group">
                    <label for="game_name_en">Nome Gioco (Inglese)</label>
                    <input type="text" id="game_name_en" name="game_name_en" required>
                </div>
                
                <div class="form-group">
                    <label for="game_image">URL Immagine</label>
                    <input type="url" id="game_image" name="game_image" required>
                </div>
                
                <div class="form-group">
                    <label for="game_page_url">URL Gioco (per iframe)</label>
                    <input type="url" id="game_page_url" name="game_page_url" required>
                </div>
                
                <button type="submit" name="add_game" class="btn">Aggiungi Gioco</button>
            </form>
        </div>
        
        <div class="admin-section">
            <h2>Giochi Esistenti (<?= count($games) ?>)</h2>
            <div class="games-grid">
                <?php foreach ($games as $game): ?>
                    <div class="game-card">
                        <img src="<?= htmlspecialchars($game['image']) ?>" alt="<?= htmlspecialchars($game['name_it']) ?>" class="game-image">
                        <div class="game-name"><?= htmlspecialchars($game['name_it']) ?></div>
                        <div class="file-info">File: <?= htmlspecialchars($game['filename']) ?></div>
                        <a href="?delete=<?= $game['id'] ?>" class="delete-btn" onclick="return confirm('Sei sicuro di voler eliminare questo gioco?')">Elimina</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="giochi.html" class="btn" target="_blank">Vedi Pagina Giochi</a>
            <a href="admin_logout.php" class="btn" style="background: #e74c3c; margin-left: 10px;">Logout</a>
        </div>
    </div>
</body>
</html>
