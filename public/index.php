<?php
declare(strict_types=1);
$questions=json_decode(file_get_contents(dirname(__DIR__).'/content/ap1/elektrotechnik.json'),true);
?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#101820"><title>MechaTraining</title><link rel="stylesheet" href="style.css"></head>
<body><header><div class="brand">⚙ <span>MECHA<span class="accent">TRAINING</span></span></div><div class="header-right"><div id="xpWidget" class="xp-widget hidden" aria-label="Erfahrungspunkte"><div class="xp-top"><span id="xpLevel">Level 1</span><span id="xpText">0 / 100 XP</span></div><div class="xp-track" role="progressbar" aria-label="Fortschritt zum nächsten Level" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="xpTrack"><div id="xpFill"></div></div></div></div></header>
<main><section class="intro"><p class="eyebrow">PRÜFUNGSVORBEREITUNG · MECHATRONIK</p><h1>Technik verstehen.<br><span class="accent">Wissen trainieren.</span></h1><p>Lerne mit kurzen Aufgaben und speichere deinen Fortschritt geräteübergreifend.</p></section>
<section id="loginPanel" class="panel"><h2>Anmelden</h2><p>Noch kein Administrator eingerichtet? <a href="setup.php">Ersteinrichtung</a></p><form id="loginForm"><label>Benutzername<input name="username" autocomplete="username" required></label><label>Passwort<input name="password" type="password" autocomplete="current-password" required></label><button>Anmelden</button><p id="loginError" role="alert"></p></form></section>
<section id="appPanel" class="panel hidden"><div class="toolbar"><div><p class="eyebrow">LERNBEREICH AP1</p><h2>Elektrotechnik – Grundlagen</h2></div><button id="logout" class="secondary">Abmelden</button></div><p>Angemeldet als <strong id="username"></strong> · <span id="progress">0</span> von 5 Aufgaben richtig gelöst</p><div class="meter"><div id="bar"></div></div><div id="quiz"></div></section>
<section id="adminPanel" class="panel hidden"><h2>Benutzerverwaltung</h2><p>Nur Administratoren können neue Benutzer anlegen.</p><form id="createUserForm"><label>Neuer Benutzername<input name="username" required minlength="3" maxlength="32" pattern="[a-zA-Z0-9_-]+"></label><label>Startpasswort (mindestens 12 Zeichen)<input name="password" type="password" required minlength="12" autocomplete="new-password"></label><button>Benutzer anlegen</button><p id="adminMessage" role="status"></p></form><h3>Vorhandene Benutzer</h3><ul id="usersList"></ul></section><footer>MechaTraining · Version 0.1 · AP2 in Vorbereitung</footer></main>

<button id="calcToggle" class="calc-toggle" type="button" aria-controls="calcPanel" aria-expanded="false" title="Wissenschaftlichen Taschenrechner öffnen">∑ <span>Rechner</span></button>
<div id="calcBackdrop" class="calc-backdrop hidden"></div>
<aside id="calcPanel" class="calc-panel" aria-label="Wissenschaftlicher Taschenrechner" aria-hidden="true" inert>
 <div class="calc-heading"><div><strong>Wissenschaftlicher Rechner</strong><small>MechaTraining · Scientific</small></div><button id="calcClose" class="calc-close" type="button" aria-label="Rechner schließen">×</button></div>
 <div class="calc-screen"><div id="calcExpression" class="calc-expression" aria-label="Rechenausdruck"></div><output id="calcDisplay" class="calc-display" aria-live="polite">0</output></div>
 <div class="calc-controls"><button type="button" id="calcAngle" title="Winkelmaß umschalten">DEG</button><button type="button" data-calc="ANS">Ans</button><button type="button" data-calc="MC">MC</button><button type="button" data-calc="MR">MR</button><button type="button" data-calc="M+">M+</button></div>
 <div class="calc-keys">
 <button data-calc="sin(">sin</button><button data-calc="cos(">cos</button><button data-calc="tan(">tan</button><button data-calc="ln(">ln</button><button data-calc="log(">log</button>
 <button data-calc="asin(">sin⁻¹</button><button data-calc="acos(">cos⁻¹</button><button data-calc="atan(">tan⁻¹</button><button data-calc="sqrt(">√</button><button data-calc="^2">x²</button>
 <button data-calc="(">(</button><button data-calc=")">)</button><button data-calc="^">xʸ</button><button data-calc="!">x!</button><button data-calc="DEL">DEL</button>
 <button data-calc="7">7</button><button data-calc="8">8</button><button data-calc="9">9</button><button data-calc="/">÷</button><button data-calc="AC" class="calc-warn">AC</button>
 <button data-calc="4">4</button><button data-calc="5">5</button><button data-calc="6">6</button><button data-calc="*">×</button><button data-calc="pi">π</button>
 <button data-calc="1">1</button><button data-calc="2">2</button><button data-calc="3">3</button><button data-calc="-">−</button><button data-calc="e">e</button>
 <button data-calc="0">0</button><button data-calc=".">.</button><button data-calc="%">%</button><button data-calc="+">+</button><button data-calc="=" class="calc-equals">=</button>
 </div><p class="calc-hint">Tastatur: Zahlen, Operatoren, Enter, Rücktaste, Esc · Dezimalpunkt oder Komma</p>
 <div class="calc-history-head"><strong>Verlauf</strong><button type="button" id="calcClearHistory">Löschen</button></div><div id="calcHistory" class="calc-history"></div>
</aside>
<script src="calculator.js" defer></script>\n<script id="questions" type="application/json"><?=json_encode($questions['questions'],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?></script><script src="app.js" defer></script></body></html>
