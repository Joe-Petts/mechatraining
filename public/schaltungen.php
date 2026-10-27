<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/bootstrap.php';
start_session();
if (!isset($_SESSION['uid'])) { header('Location: ./'); exit; }
header('Cache-Control: no-store');
?><!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Schaltung · MechaTraining</title><link rel="stylesheet" href="main.css?v=22"></head><body id="circuitPage"><header><a class="brand brand-link" href="./">⚙ MECHATRAINING</a><a href="./">← Startseite</a></header><main id="symbolCourse"><p class="sc-eyebrow">ELEKTROTECHNIK · AP1</p><h1>Schaltung</h1><p>Vom einzelnen Schaltzeichen zur funktionierenden Schaltung.</p><div class="sc-modules"><a class="sc-module" href="schaltsymbole.php"><span>01 · GRUNDLAGEN</span><h2>Schaltzeichen verstehen</h2><p>16 Symbole einzeln kennenlernen: Aussehen, Bedeutung und Anwendung. Mit kurzen Aufgaben nach jeder Erklärung.</p><strong>Symbole lernen →</strong></a><a class="sc-module" href="schaltung.php"><span>02 · ANWENDUNG</span><h2>Aktive Selbsthalteschaltung</h2><p>Bauteile im Stromlaufplan wiederfinden, schalten, messen und Fehler suchen.</p><strong>Schaltung ausprobieren →</strong></a></div></main></body></html>
