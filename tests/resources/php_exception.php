#!/usr/bin/env php
<?php

error_reporting(-1);
ini_set('display_errors', 0);

throw new RuntimeException("It's an error!\n", 42);
