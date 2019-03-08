<?php

if (!function_exists('asset')) {
    /**
     * Return assets folder with input path
     * @param $asset_path
     * @return string
     */
    function asset($asset_path)
    {
        return '/assets/' . $asset_path;
    }
    
    // File versioning makes sure that the correct files are downlaod and old files are not used in cash. .htaccess strips the versioning before delivering the file.
    function auto_version($file)
    {
        $ofile = $file;
        $u = curPageURL($dir = true);

        if ( $u == '/forgot/update' && ! strpos($u, '/forgot')) {
            $u = str_replace('/forgot','',$u);
        }

        while (strpos($file, '../') === 0) {
            // remove from file var
            $file = substr($file, 3);
            $slash = strripos($u, "/");
            $u = substr($u, 0, $slash);
        }
        $u = $u . '/' . $file;
        
        if (strpos($file, '/') !== 0 || ! file_exists($_SERVER['DOCUMENT_ROOT'] . $u)) {
            $mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $u);
        }
        if (curPageURL($dir = true) == '/forgot/update') {
            return '../' . preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $ofile);
        }
        return preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $ofile);
    }
    
    function curPageURL($dir = false)
    {
        if ($dir) {
            $url = $_SERVER["REQUEST_URI"];
            $slash = strripos($url, "/");
            $pageURL = substr($url, 0, $slash);
        } else {
            $pageURL = $url;
        }
        return $pageURL;
    }
    
    function date_format_by_timezone($time, $format, $timezone=null)
    {
        $CI =& get_instance();
        $time_format = $CI->session->userdata('timeformat');
        
        if ($time_format == 1) {
            $format = str_replace('H', 'h', $format);
            $format = str_replace('G', 'g', $format);
            if (strpos($format, 'h:') !== false) {
                if (strpos(strtolower($format), ' a') === false && strpos(strtolower($format), 'a') === false) {
                    $format .= ' A';
                }
            } else if (strpos($format, 'g:') !== false) {
                if (strpos(strtolower($format), ' a') === false && strpos(strtolower($format), 'a') === false) {
                    $format .= ' A';
                }
            }
        } else {
            if (strpos($format, 'h') !== false) {
                $format = str_replace('h', 'H', $format);
            } else if (strpos($format, 'g') !== false) {
                $format = str_replace('g', 'G', $format);
            }
            $format = str_replace(' A', '', $format);
            $format = str_replace(' a', '', $format);
            $format = str_replace('A', '', $format);
            $format = str_replace('a', '', $format);
        }
        
        return date_by_timezone($time, $format, $timezone);
    }
    
    function date_by_timezone($time, $format, $timezone=null)
    {
        if (!$time) {
            return "";
        }
        if (!$timezone) {
            $CI =& get_instance();
            $timezone = $CI->session->userdata('timezone');
        }
        if (!$timezone) {
            $timezone = 'America/Denver';
        }
        
        date_default_timezone_set($timezone);
        return date($format, $time);
    }
    
    function update_translation($worker_id)
    {
        $CI =& get_instance();
        
        $CI->load->model(['worker_model', 'work_board_model', 'company_translation_model']);
        
        
        $worker = $CI->worker_model->get_by_attributes(['worker_id' => $worker_id]);
        
        $company_translation = $CI->company_translation_model->get(['cid' => $worker->c_id, 'did' => $worker->d_id]);
        
        if (!$company_translation->active) {
            return false;
        }
        
        $CI->load->config('google');
        $apiK = $CI->config->item('translation_key');
        
        $workboard = $CI->work_board_model->getWorkboardByWorker($worker);
        if (!$workboard) {
            return false;
        }
        
        $transPackage = packageNoteTrans($workboard->work_board_id, $worker->c_id, $worker->d_id, $worker_id);
        
        $googleNoteTrans = TranslateNotes($apiK, $worker->c_id, $worker->d_id, $company_translation->pri_language, $transPackage['google']);
        
        updateAndAddTranslation($transPackage['asb'], $googleNoteTrans);
        
        $transNote = false;
        $workboards = $CI->work_board_model->getWorkboardByCompany($worker->c_id);
        foreach ($workboards as $workboard) {
            // check for main note tranlation
            // list active language for that day
            $selectedlanguages = activeLanguageForTheDay($workboard->work_board_id, $workboard->c_id, $workboard->d_id, $company_translation->pri_language);
            
            if ($selectedlanguages !== false) {
                // translate languages are present. Check to see if there are any notes that need to be updated
                $transNote = updateaddtranslatednote($apiK, $workboard->c_id, $workboard->d_id, $workboard->work_board_id, $company_translation->pri_language, $selectedlanguages);
                
                $transNote = $transNote || translateWbTaskNotes($apiK, $workboard->c_id, $workboard->d_id, $workboard->work_board_id, $company_translation->pri_language, $selectedlanguages, 'activeboard');
            }
        }
        
        if ($googleNoteTrans || $transNote) {
            return true;
        }
        
        return false;
    }
    
    function translateWbTaskNotes($apiK, $cid, $did, $work_board_id, $sourceLang, $selectedlanguages, $location='')
    {
        $CI =& get_instance();
        $db = (array)get_instance()->db;
        $dbc = mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);
        
        $sql = "SELECT id, notes
                FROM workboard_task_notes
                WHERE work_board_id = $work_board_id AND send_for_translation = 1";
        $query = $CI->db->query($sql);
        $workboard_task_notes = $query->result_array();
    
        if ($workboard_task_notes) {
            $count = 0;
            
            foreach ($workboard_task_notes as $workboard_task_note) {
                $note = $workboard_task_note['notes'];
                foreach ($selectedlanguages as $langcode => $active) {
                    $googletranslation = '';
                    if ($note) {
                        $note = nl2br($note);
                        $note = rawurlencode($note);
    
                        if ($note != '') {
                            $count += mb_strlen($note, 'UTF-8');
                            $url = "https://www.googleapis.com/language/translate/v2?key=$apiK&source=$sourceLang&target=$langcode&q=$note";
                            $googletranslation = get_content($url);
                            $googletranslation = mysqli_real_escape_string($dbc, $googletranslation['data']['translations'][0]['translatedText']);
                        }
                    }
    
                    $sql1 = 'SELECT id FROM workboard_task_notes_translation
                            WHERE workboard_task_notes_id = ' . $workboard_task_note['id'] . ' AND lang_code = "' . $langcode . '"';
                    
                    $query1 = $CI->db->query($sql1);
                    $data1 = $query1->result_array();
                    
                    if ($data1) {
                        $upsert = "UPDATE `workboard_task_notes_translation` SET `trans_note` = '$googletranslation' WHERE `id` = " . $data1[0]['id'];
                    } else {
                        $upsert = "INSERT INTO `workboard_task_notes_translation` (`workboard_task_notes_id`, `lang_code`, `trans_note`) VALUES (" . $workboard_task_note['id'] . ", '$langcode', '$googletranslation');";
                    }
                    $CI->db->query($upsert);
                    
                    $upsert1 = "UPDATE `workboard_task_notes` SET `send_for_translation` = 0 WHERE `id` = " . $workboard_task_note['id'];
                    $CI->db->query($upsert1);
                }
            }
    
            if ($count > 0) {
                addTranslationCounts($cid, $did, $count, $location);
                
                return true;
            }
        }
        
        return false;
    }
    
    function updateaddtranslatednote($apiK, $cid, $did, $wid, $source, $selectedLang)
    {
        $CI =& get_instance();
        $count = 0;
        
        $db = (array)get_instance()->db;
        $dbc = mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);
        
        $sql = "SELECT split_notes.sb_notes_id, split_notes.split_notes, split_notes.unixtime_note_modified
            FROM work_board INNER JOIN split_notes ON work_board.work_board_id = split_notes.workboard_id
            WHERE work_board.work_board_id = $wid";
        
        $query = $CI->db->query($sql);
        $data = $query->result_array();
        
        if ($data) {
            foreach ($data as $row) {
                $note = $row['split_notes'];
                foreach ($selectedLang as $langcode => $active) {
                    $googletranslation = '';
                    if ($note) {
                        $note = nl2br($note);
                        $note = rawurlencode($note);
                        
                        if ($note != '') {
                            $count += mb_strlen($note, 'UTF-8');
                            $url = "https://www.googleapis.com/language/translate/v2?key=$apiK&source=$source&target=$langcode&q=$note";
                            $googletranslation = get_content($url);
                            $googletranslation = mysqli_real_escape_string($dbc, $googletranslation['data']['translations'][0]['translatedText']);
                        }
                    }
                    
                    $sql1 = "SELECT workboard_trans_id
                        FROM workboard_notes_translation
                        WHERE split_board_notes_id = " . $row['sb_notes_id'] . " AND lang_code = '$langcode' LIMIT 1";
                    $query1 = $CI->db->query($sql1);
                    $data1 = $query1->result_array();
                    
                    if ($data1) {
                        $upsert = "UPDATE `workboard_notes_translation` SET `unixupdate` = UNIX_TIMESTAMP(), `workboard_note` = '$googletranslation' WHERE `workboard_notes_translation`.`workboard_trans_id` = " . $data1[0]['workboard_trans_id'];
                    } else {
                        $upsert = "INSERT INTO `workboard_notes_translation` (`lang_code`, `unixupdate`, `workboard_note`, `split_board_notes_id`) VALUES ('$langcode', UNIX_TIMESTAMP(), '$googletranslation', " . $row['sb_notes_id'] . ");";
                    }
                    $CI->db->query($upsert);
                    
                }
            }
            if ($count > 0) {
                addTranslationCounts($cid, $did, $count);
                return true;
            }
        }
        
        return false;
    }
    
    function activeLanguageForTheDay($wid, $cid, $did, $mainLang)
    {
        $CI =& get_instance();
        $res = array();
        $sql = "SELECT work_board_task.lang_code 
                FROM work_board_task 
                INNER JOIN department_active_lang ON work_board_task.lang_code = department_active_lang.lang_code 
                WHERE work_board_task.work_board_id = $wid AND work_board_task.lang_code <> '0' 
                AND work_board_task.lang_code <> '$mainLang' AND department_active_lang.cid = $cid AND department_active_lang.did = $did";
        
        $query = $CI->db->query($sql);
        $data = $query->result_array();
        
        if ($data) {
            foreach ($data as $row) {
                $res[$row['lang_code']] = 1;
            }
        }
        return $res;
    }
    
    function updateAndAddTranslation($ASBTransArray, $googleReturnTransArray)
    {
        $CI =& get_instance();

        $db = (array)get_instance()->db;
        $dbc = mysqli_connect($db['hostname'], $db['username'], $db['password'], $db['database']);
        
        $updateAdd = array();
        $updateAdd['add'] = array();
        $updateAdd['update'] = array();
        $updateAdd['updatetasks'] = array();
    
        if (is_array($ASBTransArray) && count($ASBTransArray) > 0) {
            // loop through Language
    
            foreach ($ASBTransArray as $kl => $vl) {
                $langCode = $kl;
                // get Translations
                foreach ($vl as $kt => $vt) {
                    if ($googleReturnTransArray[$langCode][$kt]) {
                        $translation = $googleReturnTransArray[$langCode][$kt];
                    } else {
                        $translation = '';
                    }
                    // check for multiple departments with same note
                    foreach ($vt['taskids'] as $ktask => $vtask) {
                        $tempArray = array(
                            'tnote' => $translation,
                            'lang' => $langCode,
                            'tid' => $ktask,
                            'tnid' => $vtask
                        );
    
                        if ($vtask == 0) {
                            // add
                            $updateAdd['add'][] = $tempArray;
                            $updateAdd['updatetasks'][] = $ktask;
                        } else {
                            // update
                            $updateAdd['update'][] = $tempArray;
                            $updateAdd['updatetasks'][] = $ktask;
                        }
                    }
                }
            }
    
            // update translations
            $updatearray = array();
            $updatewhere = array();
            if (count($updateAdd['update']) > 0) {
                foreach ($updateAdd['update'] as $uv) {
                    $id = $uv['tid'];
                    $tnid = $uv['tnid'];
                    $trans = mysqli_real_escape_string($dbc, $uv['tnote']);
                    $updatearray[] = "WHEN `employee_note_trans_id` = $tnid THEN '$trans'";
                    $updatewhere[] = $tnid;
                }
                $updateCase = implode(" ", $updatearray);
                $updateWhere = implode(", ", $updatewhere);
                $sql = "UPDATE `employee_notes_translation` SET `trans_note` = CASE " . $updateCase . " END WHERE `employee_note_trans_id` IN ($updateWhere)";
                $CI->db->query($sql);
                
            } // end update translation
            // add translations
            if (count($updateAdd['add']) > 0) {
    
                $addarray = array();
                foreach ($updateAdd['add'] as $av) {
    
                    $id = $av['tid'];
                    $lang = $av['lang'];
                    $trans = mysqli_real_escape_string($dbc, $av['tnote']);
                    $addarray[] = "($id, '$lang', '$trans')";
                }
    
                if ($addarray) {
                    // print_r($addarray);
                    $addarray = implode(', ', $addarray);
                    $sql = "INSERT INTO `employee_notes_translation` (`wb_task_id`, `lang_code`, `trans_note`) VALUES $addarray;";
                    $CI->db->query($sql);
                }
            } // end add translation
            if (count($updateAdd['updatetasks']) > 0) {
    
                $where = implode(', ', $updateAdd['updatetasks']);
                $sql = "UPDATE `work_board_task` SET `send_for_translation` = 0 WHERE `work_board_task`.`wb_task_id` In ($where);";
                $CI->db->query($sql);
            }
    
            // update tasks in the workboard task to 0;
        }
    }
    
    function packageNoteTrans($workboard_id, $c_id, $d_id = 1, $worker_id)
    {
        $CI =& get_instance();
        
        $sql = "SELECT work_board_task.wb_task_id, work_board_task.send_for_translation, 
                    work_board_task.task_notes, workers.lang_code, employee_notes_translation.employee_note_trans_id
                FROM department_active_lang
                INNER JOIN workers ON department_active_lang.lang_code = workers.lang_code
                INNER JOIN work_board_task ON work_board_task.worker_id = workers.worker_id
                LEFT JOIN employee_notes_translation ON work_board_task.wb_task_id = employee_notes_translation.wb_task_id
                WHERE work_board_task.send_for_translation = 1 AND workers.lang_code IS NOT NULL 
                    AND work_board_task.work_board_id = $workboard_id AND department_active_lang.cid = $c_id 
                    AND department_active_lang.did = $d_id";
        if ($worker_id) {
            $sql .= " AND workers.worker_id = " . $worker_id;
        }
        
        $query = $CI->db->query($sql);
        $data = $query->result_array();
        
        $transPackage = array();
        $transPackage['asb'] = array();
        $transPackage['google'] = array();
        
        if ($data) {
            $activeLangCodes = activelanguages($c_id, $d_id, 'lang_code', 'active_lang_id');
            foreach ($data as $row) {
                // make array
                // Language
                if (isset($activeLangCodes[$row['lang_code']])) {
                    if (! isset($transPackage['asb'][$row['lang_code']])) {
                        $transPackage['asb'][$row['lang_code']] = array();
                        $transPackage['google'][$row['lang_code']] = array();
                    }
                    // array
                    // check for existing note translation
    
                    // create new
                    if (is_null($row['employee_note_trans_id'])) {
                        $updateid = 0;
                    } else {
                        $updateid = $row['employee_note_trans_id'];
                    }
                    $arrayMatch = array_search($row['task_notes'], array_column($transPackage['asb'][$row['lang_code']], 'note'));
    
                    if ($arrayMatch === false) {
                        $transPackage['asb'][$row['lang_code']][] = array(
                            'note' => $row['task_notes'],
                            'taskids' => array(
                                $row['wb_task_id'] => $updateid
                            )
                        );
                        $transPackage['google'][$row['lang_code']][] = $row['task_notes'];
                    } else {
                        $transPackage['asb'][$row['lang_code']][$arrayMatch]['taskids'][$row['wb_task_id']] = $updateid;
                    }
                }
                // notes
                // task ids
            }
        }
    
        return $transPackage;
    }
    
    /**
     * 
     * @param int $c_id
     * @param int $d_id
     * @param string $key  active_lang_id, lang_name, lang_code
     * @param string $value  active_lang_id, lang_name, lang_code
     * @return multitype:unknown
     */
    function activelanguages($c_id, $d_id, $key = "lang_code", $value = "lang_name")
    {
        $CI =& get_instance();
        $lang = array();
        
        $sql = "SELECT department_active_lang.active_lang_id, supported_lang.lang_name, supported_lang.lang_code
                FROM department_active_lang INNER JOIN supported_lang ON department_active_lang.lang_code = supported_lang.lang_code
                WHERE (((department_active_lang.cid)=$c_id) AND ((department_active_lang.did)=$d_id) AND ((department_active_lang.remove)=0))";
        
        $query = $CI->db->query($sql);
        $data = $query->result_array();
        
        foreach ($data as $row) {
            $lang[$row[$key]] = $row[$value];
        }
        return $lang;
    }
    
    function TranslateNotes($apiK, $c_id, $d_id, $source, $textarray)
    {
        // $textarray need to start of with the language=>array(note, note note);
        $textarray;
        $mysqlCountNumber = 0;
        $counterTo = 1500;
        $urlcounter = 0;
        $translation = array();
        $newTest = array();
        $trackArray = array();
        if (is_array($textarray)) {
            foreach ($textarray as $k => $v) {
                $target = $k;
                $count = 0;
                $newCount = 0;
                // only translate diffrent languages
                if ($target != $source) {
                    $translation[$target] = array();
                    foreach ($v as $note) {
                        $chrCount = mb_strlen($note, 'UTF-8');
                        if (($urlcounter + $chrCount) >= $counterTo) {
                            // send to google for traslation;
                            $q = implode('&q=', $newTest);
                            $url = "https://www.googleapis.com/language/translate/v2?key=$apiK&source=$source&target=$target&q=$q";
                            $googletranslation = get_content($url);
    
                            foreach ($googletranslation['data']['translations'] as $gk => $gv) {
                                $key = $trackArray[$gk];
                                $translation[$target][$key] = $gv['translatedText'];
                            }
                            $mysqlCountNumber += $urlcounter;
                            $urlcounter = 0;
                            $newCount = 0;
                            $trackArray = array();
                            $newTest = array();
                        }
                        $newTest[] = rawurlencode($note);
                        $trackArray[$newCount] = $count;
                        $urlcounter += $chrCount;
                        $count ++;
                        $newCount ++;
                    }
                    if (count($newTest) > 0) {
                        $trackArray[$newCount] = $count;
                        $q = implode('&q=', $newTest);
                        $url = "https://www.googleapis.com/language/translate/v2?key=$apiK&source=$source&target=$target&q=$q";
                        $googletranslation = get_content($url);
                        
                        foreach ($googletranslation['data']['translations'] as $gk => $gv) {
                            $key = $trackArray[$gk];
                            $translation[$target][$key] = $gv['translatedText'];
                        }
                        
                        $mysqlCountNumber += $urlcounter;
                        $urlcounter = 0;
                        $newCount = 0;
                        $trackArray = array();
                    }
                }
            }
            if ($mysqlCountNumber > 0) {
                addTranslationCounts($c_id, $d_id, $mysqlCountNumber);
                
            }
        }
    
        return $translation;
    }

    function get_content($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);     //We want the result to be saved into variable, not printed out
        $response = curl_exec($handle);                         
        curl_close($handle);
        if (!$response) {
            $response = file_get_contents($url);
        }
        return json_decode($response, true);
    }
    
    function addTranslationCounts($c_id, $d_id, $transCount)
    {
        $CI =& get_instance();
        
        $sql = "INSERT INTO company_translation_count (`date_of_trans`, `trans_count`, `c_id`, `d_id`) VALUES (CURRENT_DATE(), $transCount, $c_id, $d_id);";
        $CI->db->query($sql);
    }
}

?>