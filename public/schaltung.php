<?php
 declare(strict_types=1);
 require dirname(__DIR__).'/src/bootstrap.php';
 start_session();
 if (!isset($_SESSION['uid'])) { header('Location: ./'); exit; }
 header('Cache-Control: no-store');
?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Aktive Schaltung · MechaTraining</title><link rel="stylesheet" href="main.css?v=22"></head>
<body id="circuitPage"><header><a class="brand brand-link" href="./">⚙ MECHATRAINING</a><a href="schaltungen.php">← Schaltung</a></header>
<main id="circuitLab" data-game-id="ap1-self-hold-v1" data-priority="8">
<p class="cl-eyebrow">ELEKTROTECHNIK / AP1 / 24 V DC</p><h1>Schalten. Beobachten. Verstehen.</h1><p>Schritt für Schritt: Bauteile kennenlernen, Wissen festigen und anschließend selbst schalten.</p>
<div class="cl-layout"><section class="cl-board" aria-label="Schaltung"><div class="cl-controls"><button id="clPower" type="button" aria-pressed="true">24 V EIN</button><button id="clStart" type="button">S1 · Start halten</button><button id="clStop" type="button">S0 · Stopp halten</button></div><p class="cl-hint">Taster gedrückt halten, dann loslassen. Tastatur: Tab wählen, Leertaste oder Enter halten.</p>
<svg id="clDiagram" viewBox="0 0 600 570" role="img" aria-labelledby="clSvgTitle clSvgDesc">
<title id="clSvgTitle">Selbsthaltung mit K1</title><desc id="clSvgDesc">24 Volt über Sicherung F1 und Stopptaster S0. Starttaster S1 und Hilfskontakt K1 liegen parallel vor der Schützspule K1. Ein zweiter K1-Kontakt schaltet die Meldeleuchte H1.</desc>
<g class="cl-wire" id="clSupplyWire"><path d="M70 40H510 M170 40V70 M450 40V230"/></g><text x="25" y="45">+24 V</text>
<g class="cl-symbol"><rect x="157" y="70" width="26" height="44"/><path id="clFuseLink" d="M170 70V114"/></g><text x="205" y="98">F1</text>
<path class="cl-wire" id="clFuseWire" d="M170 114V150"/>
<g class="cl-symbol"><circle cx="170" cy="150" r="3"/><circle cx="170" cy="190" r="3"/><path id="clStopBlade" d="M170 150V190"/></g><text x="205" y="172">S0 · Öffner</text><text class="cl-small" x="130" y="150">11</text><text class="cl-small" x="130" y="196">12</text>
<path class="cl-wire" id="clStopWire" d="M170 190V215H290V260 M170 215H110V260"/>
<g class="cl-symbol"><circle cx="110" cy="260" r="3"/><circle cx="110" cy="310" r="3"/><path id="clStartBlade" d="M110 310L85 266"/><circle cx="290" cy="260" r="3"/><circle cx="290" cy="310" r="3"/><path id="clHoldBlade" d="M290 310L265 266"/><circle cx="450" cy="230" r="3"/><circle cx="450" cy="280" r="3"/><path id="clLampBlade" d="M450 280L425 236"/></g>
<text x="40" y="245">S1</text><text x="310" y="285">K1</text><text x="475" y="255">K1</text><text class="cl-small" x="75" y="258">13</text><text class="cl-small" x="75" y="325">14</text><text class="cl-small" x="310" y="260">13</text><text class="cl-small" x="310" y="315">14</text><text class="cl-small" x="475" y="231">23</text><text class="cl-small" x="475" y="281">24</text>
<path class="cl-wire" id="clCoilWire" d="M110 310V350H290V310 M170 350V400"/><path class="cl-wire" id="clLampWire" d="M450 280V400"/>
<g id="clCoil" class="cl-symbol"><rect x="140" y="400" width="60" height="54"/></g><text x="156" y="434">K1</text><text class="cl-small" x="210" y="409">A1</text><text class="cl-small" x="210" y="454">A2</text>
<g id="clLamp" class="cl-symbol"><circle cx="450" cy="427" r="27"/><path d="M431 408L469 446 M469 408L431 446"/></g><text x="490" y="434">H1</text>
<path class="cl-wire" d="M170 454V510 M450 454V510 M70 510H510"/><text x="25" y="515">0 V</text><text class="cl-small" x="85" y="553">Steuerstromkreis</text><text class="cl-small" x="370" y="553">Meldezweig</text>
</svg><p id="clState" role="status"></p><p class="cl-hint">Blau gestrichelt = aktuell erklärtes Bauteil. Orange = +24-V-Potenzial, nicht automatisch Stromfluss. Ideales Funktionsmodell; kein Motor-Leistungskreis.</p></section>
<section class="cl-work"><label class="cl-hint" for="clRevisit">Bereits gelernt · Schritt erneut öffnen</label><select id="clRevisit" hidden aria-label="Bereits gelernten Schritt öffnen"></select><p id="clProgress" class="cl-eyebrow"></p><h2 id="clTitle" tabindex="-1"></h2><p id="clTask"></p><p id="clTip" class="cl-tip" hidden></p><div id="clAnswer"></div><button id="clCheck" type="button">Prüfen</button><p id="clFeedback" role="status"></p><button id="clPrevious" type="button" hidden>← Zurück</button><button id="clNext" type="button" hidden>Weiter →</button>
<details><summary>Messgerät · Gleichspannung</summary><label for="clProbe">Messpunkt gegen 0 V (A2)</label><select id="clProbe"><option value="supply">Versorgung +24 V</option><option value="afterFuse">Hinter F1</option><option value="afterStop">Hinter S0</option><option value="coil">K1 · A1</option></select><output id="clMeter" aria-live="polite"></output><p>Die schwarze Messleitung liegt an 0 V. Die rote liegt am ausgewählten Messpunkt.</p></details>
<details><summary>Bauteile und Funktion</summary><p>S0 ist ein Öffner: Beim Drücken unterbricht er den Stromkreis. S1 ist ein Schließer: Er leitet nur beim Drücken.</p><p>Die Spule K1 schließt ihre Hilfskontakte. K1 (13–14) überbrückt S1 und hält die Spule nach dem Loslassen eingeschaltet. K1 (23–24) schaltet H1.</p><p>Stoppen oder Spannungsausfall lässt K1 abfallen. Nach Spannungsrückkehr ist ohne gedrückten Starttaster kein Wiederanlauf möglich.</p></details>
<button id="clResume" type="button" hidden>Zurück zum Lernweg</button><button id="clReset" type="button">Übung neu starten</button><button id="clFree" type="button">Frei experimentieren</button><div id="clFaultBox" hidden><label for="clFault">Fehler einsetzen</label><select id="clFault"><option value="none">Kein Fehler</option><option value="hold">K1-Haltekontakt unterbrochen</option><option value="fuse">F1 unterbrochen</option></select></div>
<p class="cl-hint">Übungsstand gilt für diese Sitzung. Keine XP-Vergabe.</p></section></div></main>
<script src="circuit-model.js?v=1" defer></script><script src="circuit-lab.js?v=2" defer></script></body></html>
