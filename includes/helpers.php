<?php
if (!defined('ABSPATH')) exit;

function indices_estar_current_user_can_manage(): bool { return current_user_can('manage_options'); }
function indices_estar_sanitize_year($v): int { return max(0, (int)$v); }
function indices_estar_sanitize_number($v): int { return max(0, (int)$v); }
function indices_estar_sanitize_date($v): string {
  $v = is_string($v) ? trim($v) : '';
  if ($v === '') return '';
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
}
function indices_estar_bool_to_int($v): int { return !empty($v) ? 1 : 0; }
