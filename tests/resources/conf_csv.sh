#!/usr/bin/env bash

. "{log_dir}/conf.sh"

# Expected output format: {'txt', 'csv'}
SUPERVISOR_OUTPUT_FORMAT='csv'

# Number of the output CSV's field containing messages to watch (1-based):
SUPERVISOR_CSV_FIELD_TO_SCAN=3

# Set the CSV field separator (one character only):
SUPERVISOR_CSV_FIELD_SEPARATOR=';'

# Set the CSV field enclosure (one character only):
SUPERVISOR_CSV_FIELD_ENCLOSURE='|'

# Path to 'csv-parser.awk' CSV parser
# @see https://github.com/geoffroy-aubry/awk-csv-parser for more details.
SUPERVISOR_CSV_PARSER="$ROOT_DIR/vendor/bin/csv-parser.awk"
