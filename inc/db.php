<?php
    //------------------------------------------------------------------------------------------
    function db_connect() {
    //------------------------------------------------------------------------------------------
        global $DB;
        switch($DB['type']) {
            case DB_POSTGRESQL:
                $conn = "pgsql:dbname={$DB['db']};host={$DB['host']};port={$DB['port']};options='--client_encoding=UTF8'";
                break;
            case DB_MYSQL:
                $conn = "mysql:dbname={$DB['db']};host={$DB['host']};port={$DB['port']};";
                break;
        }

        try {
            return new PDO($conn, $DB['user'], $DB['pass']);
        }
        catch(PDOException $e) {
            return FALSE;
        }
    }

    //------------------------------------------------------------------------------------------
    function db_esc($name, $qualifier = null) {
    //------------------------------------------------------------------------------------------
        global $DB;
        switch($DB['type']) {
            case DB_POSTGRESQL:
                $escape_char = '"';
                $separator_char = '.';
                break;
            case DB_MYSQL:
                $escape_char = '`';
                $separator_char = '.';
                break;
            default:
                return proc_error(l10n('error.invalid-dbtype', $DB['type']));
        }

        if($name[0] == $escape_char)
            return $name; // already escaped

        if($qualifier !== null)
            return $escape_char . $qualifier . $escape_char . $separator_char . $escape_char . $name . $escape_char;
        else
            return $escape_char . $name . $escape_char;
    }

    //------------------------------------------------------------------------------------------
    // $return_escaped:
    //   if NULL, it will return escaped only of $fieldname is already escaped, otherwise not
    //   if TRUE/FALSE, it will/will not escape the postfixed fieldname
    function db_postfix_fieldname($fieldname, $postfix, $return_escaped) {
    //------------------------------------------------------------------------------------------
        global $DB;

        switch($DB['type']) {
            case DB_POSTGRESQL:
                $escape_char = '"';
                break;

            case DB_MYSQL:
                $escape_char = '`';
                break;

            default:
                return proc_error(l10n('error.invalid-dbtype', $DB['type']));
        }

        $fieldname_unescaped = trim($fieldname, $escape_char);
        $was_escaped = ($fieldname_unescaped == $fieldname);
        $do_escape = ($return_escaped === TRUE || ($return_escaped === NULL && $was_escaped === TRUE));

        if(!$do_escape)
            $escape_char = '';

        return "{$escape_char}{$fieldname}{$postfix}{$escape_char}";
    }

    //------------------------------------------------------------------------------------------
    function db_get_single_val($sql, $params, &$retrieved_value, $db = false) {
    //------------------------------------------------------------------------------------------
        if($db === false)
            $db = db_connect();
        if($db === false)
            return proc_error(l10n('error.db-connect'));

        $stmt = $db->prepare($sql);
        if($stmt === false)
            return proc_error(l10n('error.db-prepare'), $db);

        if(false === $stmt->execute($params))
            return proc_error(l10n('error.db-execute'), $stmt);

        $retrieved_value = $stmt->fetchColumn();
        return true;
    }

    //------------------------------------------------------------------------------------------
    function db_get_single_row($sql, $params, &$row, $db = false) {
    //------------------------------------------------------------------------------------------
        if($db === false)
            $db = db_connect();
        if($db === false)
            return proc_error(l10n('error.db-connect'));

        $stmt = $db->prepare($sql);
        if($stmt === false)
            return proc_error(l10n('error.db-prepare'), $db);

        if(false === $stmt->execute($params))
            return proc_error(l10n('error.db-execute'), $stmt);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return true;
    }

    //------------------------------------------------------------------------------------------
    function db_prep_exec($sql, $params, &$stmt, $db = false) {
    //------------------------------------------------------------------------------------------
        if($db === false)
            $db = db_connect();
        if($db === false)
            return proc_error(l10n('error.db-connect'));
        $stmt = $db->prepare($sql);
        if($stmt === false)
            return proc_error(l10n('error.db-prepare'), $db);
        if(false === $stmt->execute($params))
            return proc_error(l10n('error.db-execute'), $stmt);
        return true;
    }

    //------------------------------------------------------------------------------------------
    function db_array_to_json_array_agg($expr, $cast_to_text = true) {
    //------------------------------------------------------------------------------------------
        global $DB;
        switch($DB['type']) {
            case DB_POSTGRESQL:
                return "array_to_json(array_agg($expr))";
            case DB_MYSQL:
                if($cast_to_text)
                    $expr = db_cast_text($expr);
                return "concat('[',group_concat(json_quote($expr) separator ','),']')";
        }
    }

    //------------------------------------------------------------------------------------------
    function db_array_to_string_array_agg($expr, $separator) {
    //------------------------------------------------------------------------------------------
        global $DB;
        switch($DB['type']) {
            case DB_POSTGRESQL:
                return "array_to_string(array_agg($expr), '$separator')";
            case DB_MYSQL:
                return "group_concat(($expr) separator '$separator')";
        }
    }

    //------------------------------------------------------------------------------------------
    function db_cast_text($expr) {
    //------------------------------------------------------------------------------------------
        global $DB;
        switch($DB['type']) {
            case DB_POSTGRESQL:
                return "$expr::text";
            case DB_MYSQL:
                return "cast($expr as char)";
        }
    }
?>
