<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
start_session();
if (!isset($_SESSION['uid'])) { header('Location: ./'); exit; }
header('Cache-Control: no-store');
?><!doctype html>
<html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SPS lernen · MechaTraining</title><link rel="stylesheet" href="main.css?v=23"></head>
<body id="spsLearnPage"><header><a class="brand brand-link" href="./">⚙ MECHATRAINING</a><a href="./">← Startseite</a></header>
<main id="spsCourse" data-user="<?= (int)$_SESSION['uid'] ?>" data-game-id="ap1-sps-learn-v1" data-priority="10">
<p class="sl-eyebrow">VON GRUND AUF · AP1</p><h1>SPS lernen</h1><p class="sl-intro">Du brauchst kein Vorwissen. Lerne zuerst die Begriffe kennen, probiere die Zusammenhänge aus und prüfe dann dein Verständnis.</p>
<div class="sl-layout"><nav class="sl-nav" aria-label="Lektionen"><h2>Dein Lernweg</h2><ol id="slLessons"></ol><p id="slProgress" role="status"></p><button id="slRestart" type="button" class="sl-secondary">Lernweg neu beginnen</button></nav>
<article class="sl-card"><p id="slStep" class="sl-eyebrow"></p><h2 id="slTitle" tabindex="-1"></h2><div id="slExplanation"></div><aside class="sl-example"><h3>Ein Beispiel aus der Praxis</h3><p id="slExample"></p></aside><p id="slRemember" class="sl-remember"></p>
<section id="slDemo" class="sl-demo" aria-label="Zusammenhang ausprobieren"></section>
<form id="slCheck" class="sl-check"><fieldset><legend id="slQuestion"></legend><div id="slAnswers"></div></fieldset><button id="slSubmit" type="submit">Verständnis prüfen</button></form><p id="slFeedback" role="status" aria-live="polite"></p>
<div class="sl-actions"><button id="slBack" type="button" class="sl-secondary">← Zurück</button><button id="slNext" type="button" disabled>Weiter →</button></div>
<section id="slComplete" hidden><h3>Die Grundlagen sitzen.</h3><p>Du hast alle zwölf Verständnisübungen gelöst. Auf der Startseite kannst du unter „SPS-Aufgaben · AP1“ weiterüben.</p><a href="./">Zur Startseite →</a></section></article></div>
<p class="sl-note" id="slStorage">Dieser Lernweg merkt sich deinen Stand pro Benutzer in diesem Browser. Die AP1-Aufgaben und ihre XP werden separat geführt.</p><p class="sl-note">Die Modelle sind bewusst vereinfacht. Sie zeigen Programmlogik und ideale Zeiten, keine reale Maschinensteuerung.</p><noscript>Bitte aktiviere JavaScript für den Lernweg.</noscript>
</main><script src="sps-lernen.js?v=1" defer></script></body></html>
