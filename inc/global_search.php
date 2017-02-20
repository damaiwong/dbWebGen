<?
    //==========================================================================================
    class GlobalSearch {
    //==========================================================================================
        protected static $search_term_sanitized = false;

        //--------------------------------------------------------------------------------------
        // call this before using $_GET['q']
        public static function sanitize_search_term() {
            if(!self::$search_term_sanitized) {
                if(isset($_GET['q']))
                    $_GET['q'] = trim($_GET['q'], "% \t\n\r\0\x0B");
                self::$search_term_sanitized = true;
            }
        }

        //--------------------------------------------------------------------------------------
        public static function get_cache_ttl() {
            return self::get_setting('cache_ttl', 3600);
        }

        //--------------------------------------------------------------------------------------
        protected static function /*bool*/ read_cache(&$html) {
            global $APP;
            if(!self::is_preview() || isset($_GET['nocache']) || !isset($APP['cache_dir']))
                return false;
            $dir = sprintf('%s/global_search', $APP['cache_dir']);
            self::sanitize_search_term();
            $filename = sprintf('%s/%s.html', $dir, urlencode($_GET['q']));
            $t = @filemtime($filename);
			if($t === false) // probably does not exist yet
				return false;
			if(time() - $t > self::get_cache_ttl()) // cache expired
				return false;
			$html = @file_get_contents($filename);
			if($html === false)
            	return false;
            $html = sprintf(
                '<div class="alert alert-warning"><b>Note:</b> The search results for this search term were retrieved from the cache. Fresh results for this search term will be available after the cache expires in %s minutes.</div>',
                intval((self::get_cache_ttl() - (time() - $t)) / 60)
            ) . $html;
            return true;
        }

        //--------------------------------------------------------------------------------------
        protected static function write_cache($html) {
            global $APP;
            if(!self::is_preview() || !isset($APP['cache_dir']))
                return false;
            $dir = sprintf('%s/global_search', $APP['cache_dir']);
			create_dir_if_not_exists($dir);
            self::sanitize_search_term();
            $filename = sprintf('%s/%s.html', $dir, urlencode($_GET['q']));
			return @file_put_contents($filename, $html);
        }

        //--------------------------------------------------------------------------------------
        protected static function get_table_settings(&$table /*string|array*/) {
            global $TABLES;
            return is_array($table) ? $table : ($table = $TABLES[$table]);
        }

        //--------------------------------------------------------------------------------------
        public static function get_setting($setting, $default) {
            global $APP;
            return isset($APP['global_search'][$setting]) ? $APP['global_search'][$setting] : $default;
        }

        //--------------------------------------------------------------------------------------
        public static function /*bool*/ is_table_included($table /*string|array*/) {
            global $APP;
            self::get_table_settings($table);
            if(isset($table['global_search']) && isset($table['global_search']['include_table']))
                return $table['global_search']['include_table'];
            return $APP['global_search']['include_table'];
        }

        //--------------------------------------------------------------------------------------
        public static function transliterator_rules() {
        //--------------------------------------------------------------------------------------
            global $APP;
            if(!isset($APP['global_search']) || !isset($APP['global_search']['transliterator_rules']))
    			return ':: Any-Latin; :: Latin-ASCII;';
    		return $APP['global_search']['transliterator_rules'];
    	}

        //--------------------------------------------------------------------------------------
        public static function /*bool*/ is_field_included($field) {
            if(isset($field['global_search']) && isset($field['global_search']['include_field']))
                return $field['global_search']['include_field'];
            return self::get_setting('include_field', true);
        }

        //--------------------------------------------------------------------------------------
        public static function /*bool*/ is_enabled() {
            global $APP; return isset($APP['global_search']);
        }

        //--------------------------------------------------------------------------------------
        public static function min_search_len() {
            return self::get_setting('min_search_len', 3);
        }

        //--------------------------------------------------------------------------------------
        public static function max_preview_results_per_table() {
            return self::get_setting('max_preview_results_per_table', 10);
        }

        //--------------------------------------------------------------------------------------
        public static function max_detail_results() {
            return self::get_setting('max_detail_results', 100);
        }

        //--------------------------------------------------------------------------------------
        public static function max_results_to_display() {
            return self::is_preview() ? self::max_preview_results_per_table() : self::max_detail_results();
        }

        //--------------------------------------------------------------------------------------
        public static function search_string_transformation($field = null) {
            if($field !== null) {
                // check for field level search_string_transformation
                if(isset($field['global_search']) && isset($field['global_search']['search_string_transformation']))
                    return $field['global_search']['search_string_transformation'];
            }

            global $APP;
            return self::get_setting('search_string_transformation',
                isset($APP['search_string_transformation']) ? $APP['search_string_transformation'] : '%s');
        }

        //--------------------------------------------------------------------------------------
        public static function is_preview() {
            return !isset($_GET['table']);
        }

        //--------------------------------------------------------------------------------------
        public static function /*string*/ render_searchbox() {
        //--------------------------------------------------------------------------------------
            self::sanitize_search_term();
            $mode = MODE_GLOBALSEARCH;
            $q = isset($_GET['mode']) && $_GET['mode'] == MODE_GLOBALSEARCH && isset($_GET['q']) ? unquote($_GET['q']) : '';

            return <<<HTML
            <form class="navbar-form" method="GET">
                <input type="hidden" name="mode" value="$mode" />
            	<div class="input-group">
                    <input id="global-search-box" type="text" class="form-control" placeholder="Search" name="q" value="$q" />
            		<div class="input-group-btn">
            			<button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-search"></span></button>
            		</div>
            	</div>
        	</form>
HTML;
        }

        //--------------------------------------------------------------------------------------
        public static function /*string*/ render_result() {
        //--------------------------------------------------------------------------------------
            global $TABLES;

            self::sanitize_search_term();
            $head = sprintf('<h1>Search Results for <code>%s</code></h1>', html($_GET['q']));

            $is_from_cache = self::read_cache($html);
            if($is_from_cache)
                return $html;

            if(mb_strlen($_GET['q']) < self::min_search_len())
                return $head . sprintf('<p>This search term is too short, it must contain at least %s characters.</p>', self::min_search_len());

            // to speed up, retrieve transformed value once from database
            db_get_single_val('select ' . sprintf(self::search_string_transformation(), '?'), array($_GET['q']), $transformed_search_term);

            if(!self::is_preview()) {
                if(!isset($TABLES[$_GET['table']]))
                    return $head . proc_error('Invalid table');
                return $head . self::render_table_results($_GET['table'], $TABLES[$_GET['table']], $transformed_search_term, $num_results);
            }

            $body = '';
            $total_results = 0;
            $total_tables = 0;
            $anchors = array();
            foreach($TABLES as $table_name => &$table) {
                if(self::is_table_included($table)) {
                    $body .= self::render_table_results($table_name, $table, $transformed_search_term, $num_results);
                    $total_results += $num_results;
                    if($num_results > 0) {
                        $total_tables++;
                        $anchors[] = sprintf('<a href="#%s">%s</a>', $table_name, $table['display_name']);
                    }
                }
            }
            if($total_results == 0)
                $msg = 'No search results in any table.';
            else if($total_results == 1)
                $msg = 'One search result found.';
            else {
                $msg = sprintf(
                    'Found search results in %s table%s. %s',
                    $total_tables == 1 ? 'one' : $total_tables,
                    $total_tables == 1 ? '' : 's',
                    $total_tables > 3 ? 'Click to jump to table: ' . implode(' | ', $anchors) : ''
                );
            }
            $html = $head . $msg . $body;
            self::write_cache($html);
            return $html;
        }

        //--------------------------------------------------------------------------------------
        public static function /*string*/ render_table_results($table_name, &$table, $transformed_search_term, &$num_results) {
        //--------------------------------------------------------------------------------------
            require_once 'record_renderer.php';
            require_once 'fields.php';

            self::sanitize_search_term();
            $is_preview = self::is_preview();
            $max_results = $is_preview? self::max_preview_results_per_table() : self::max_detail_results();
            $param_name = 'q';

            $select_fields = array();
            $from_conditions = array();
            $relevant_fields = array();
            foreach($table['fields'] as $field_name => &$field) {
                $is_pk = in_array($field_name, $table['primary_key']['columns']);

                if(!self::is_field_included($field))
                    continue;

                if(!($field_obj = FieldFactory::create($table_name, $field_name, $field)))
                    continue;

                if(!$is_pk && !$field_obj->is_included_in_global_search())
                    continue;

                $relevant_fields[$field_name] = $field;
                $select_fields[] = sprintf($field_obj->sql_select_transformation(), db_esc($field_name, 't'));
                if($field_obj->is_included_in_global_search())
                    $where_conditions[] = $field_obj->get_global_search_condition($param_name, self::search_string_transformation($field), 't');
            }

            $sql = sprintf(
                'SELECT %s FROM %s t WHERE %s LIMIT %s',
                implode(', ', $select_fields),
                db_esc($table_name),
                implode(' OR ', $where_conditions),
                $max_results
            );
            //debug_log($sql);

            $db = db_connect();
            if($db === false)
                return proc_error('Cannot connect to database.');
            $stmt = $db->prepare($sql);
            if($stmt === false)
                return proc_error('Failed to prepare search query.', $db);

            if(false === $stmt->execute(array($param_name => $transformed_search_term)))
    			return proc_error('Executing SQL statement failed', $db);

            $highlighter = new SearchResultHighlighter($transformed_search_term, self::transliterator_rules());
            $rr = new RecordRenderer($table_name, $table, $relevant_fields, $stmt, false, false, null, $highlighter);
            $num_results = $rr->num_results();
            if($num_results == 0)
                return ''; // don't render anything

            if($num_results < self::max_results_to_display())
                $num_msg = self::is_preview() ? '' : "$num_results search results found.";
            else {
                $show_more = self::is_preview() ?
                    sprintf('<a class="btn btn-default" href="?%s"><span class="glyphicon glyphicon-hand-right"></span> Display All Results</a>', http_build_query(array('mode' => MODE_GLOBALSEARCH, 'table' => $table_name, 'q' => $_GET['q']))) :
                    'To narrow down search results please adapt your search term.';
                $num_msg = sprintf('Only the first %s search results are shown here. %s', self::max_results_to_display(), $show_more);
            }

            $is_preview = self::is_preview() ? 'true' : 'false';
            return <<<HTML
                <h2 id="$table_name">
                    {$table['display_name']}<sup class="scroll-top"><a title="Go to Top" style="font-size:10px" href="#top"><span class="glyphicon glyphicon-arrow-up"></span></a></sup>
                </h2>
                <p>$num_msg</p>
                <div class="col-sm-12">
                    {$rr->html()}
                </div>
                <script>
                    if($is_preview) {
                        var w = $(window);
                        var sup = $('.scroll-top');
                        w.scroll(function() {
                            var top = w.scrollTop();
                            if(top > 0 && !sup.first().is(':visible'))
                                $('.scroll-top').show();
                            else if(top == 0 && sup.first().is(':visible'))
                                $('.scroll-top').hide();
                        });
                    }
                </script>
HTML;
        }
    }
?>
