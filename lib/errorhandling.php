<?php

// Set error reporting to all but notice (for now)
if (defined('E_DEPRECATED')) {
    error_reporting(E_ALL ^ (E_NOTICE | E_DEPRECATED));
} else {
    error_reporting(E_ALL ^ E_NOTICE);
}
