<?php
declare(strict_types=1);
$questions=json_decode(file_get_contents(dirname(__DIR__).'/content/ap1/elektrotechnik.json'),true);
?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#101820"><title>MechaTraining</title><link rel="stylesheet" href="style.css"></head>
<body><header><div class="brand">⚙ <span>MECHA<span class="accent">TRAINING</span></span></div><span class="tag">AP1 · TRAINING</span></header>
<main><section class="intro"><p class="eyebrow">PRÜFUNGSVORBEREITUNG · MECHATRONIK</p><h1>Technik verstehen.<br><span class="accent">Wissen trainieren.</span></h1><p>Lerne mit kurzen Aufgaben. In dieser Testversion wird dein Fortschritt nur in diesem Browser gespeichert.</p></section>
<section id="loginPanel" class="panel"><h2>Prototyp öffnen</h2><p>Einfacher Entwicklungszugang – keine persönlichen Daten eingeben.</p><form id="loginForm"><label>Zugangscode<input name="password" type="password" inputmode="numeric" autocomplete="off" required></label><button>Anmelden</button><p id="loginError" role="alert"></p></form></section>
<section id="appPanel" class="panel hidden"><div class="toolbar"><div><p class="eyebrow">LERNBEREICH AP1</p><h2>Elektrotechnik – Grundlagen</h2></div><button id="logout" class="secondary">Abmelden</button></div><p><span id="progress">0</span> von 5 Aufgaben richtig gelöst</p><div class="meter"><div id="bar"></div></div><div id="quiz"></div></section>
<footer>MechaTraining · Version 0.1 · AP2 in Vorbereitung</footer></main>
<script id="questions" type="application/json"><?=json_encode($questions['questions'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script><script src="app.js" defer></script></body></html>
