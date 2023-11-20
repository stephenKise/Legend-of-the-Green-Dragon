<?php

function getSession($valueName) {
  global $session;
  if (isset($session[$valueName]))
    return $session[$valueName];
  else
    return false;
}

function getSessionUser($valueName) {
  global $session;
  if (isset($session['user'][$valueName]))
    return $session['user'][$valueName];
  else
    return false;
}

function getSessionSuperUser() {
  global $session;
  if (!isset($session['user']['superuser']))
    return 1;
  return $session['user']['superuser'];
}
