<?php

/**
 * Application Passwords
 *
 * Plugin to manage application specific passwords.
 *
 * @version 0.1
 * @author Daniel Weuthen
 * @url http://weuthen-net.de
 */
class application_passwords extends rcube_plugin
{
    public $task = 'settings';
    public $password = '';

    function init()
    {
        $rcmail = rcmail::get_instance();
        $this->load_config();
 
        $this->add_texts('localization/');
        $this->include_script('application_passwords.js');

        // register internal plugin actions
        $this->register_action('plugin.application_passwords', array($this, 'application_passwords_init'));
        $this->register_action('plugin.application_passwords-save', array($this, 'application_passwords_save'));
        $this->register_action('plugin.application_passwords-delete', array($this, 'application_passwords_delete'));
        $rcmail->output->add_label('application_passwords.application_passwords');

    }

    function application_passwords_init()
    {

        $this->add_texts('localization/');
        $this->register_handler('plugin.body', array($this, 'application_passwords_form'));

        $rcmail = rcmail::get_instance();
        $rcmail->output->set_pagetitle(rcmail::Q($this->gettext('application_passwords')));
        $rcmail->output->send('plugin');
    }

    function application_passwords_save()
    {
        $rcmail = rcmail::get_instance();
        $this->add_texts('localization/');
        $this->register_handler('plugin.body', array($this, 'application_passwords_step2'));
        $rcmail->output->set_pagetitle(rcmail::Q($this->gettext('application_passwords')));

        $application = rcube_utils::get_input_value('new_application_name', rcube_utils::INPUT_POST, true);
        $this->password = $this->_password();
        $this->_save($application, $this->password);

        $rcmail->output->send('plugin');
    }

    function application_passwords_delete()
    {
        $rcmail = rcmail::get_instance();
        $this->add_texts('localization/');
        $this->register_handler('plugin.body', array($this, 'application_passwords_form'));
        $rcmail->output->set_pagetitle(rcmail::Q($this->gettext('application_passwords')));

        $appid = rcube_utils::get_input_value('appid', rcube_utils::INPUT_GET);
        $this->_delete($appid);

        $rcmail->output->send('plugin');
    }
	
    function application_passwords_form() 
    {
        $rcmail = rcmail::get_instance();
        
        $title = html::tag('h1',array('class' => 'boxtitle'), rcmail::Q($this->gettext('application_passwords')));

        // This creates the form for creating new application specific passwords
        $table = new html_table(array('id' => 'new_application', 'class' => 'propform', 'cellspacing' => '0', 'cols' => 3));
        $table->add(array('class' => 'title'), rcmail::Q($this->gettext('name')));
        $table->add(null, html::tag('input', array('type' => 'text', 'id' => 'new_application_name', 'name' => 'new_application_name', 'size' => '36', 'value' => '')));
        $table->add(null, html::tag('input', array('type' => 'submit', 'id' => '', 'class' => 'button mainaction', 'value' => rcmail::Q($this->gettext('create_password')))));
        

        $section_new = html::div(array('id' => 'new-application-step1', 'class' => 'boxcontent'),
                       $rcmail->output->form_tag(array('id' => 'new_application_form', 'class' => 'propform', 'name' => 'new_application_form', 'method' => 'post', 'action' => './?_task=settings&_action=plugin.application_passwords-save'),
                           html::tag('fieldset', null, html::tag('legend', null, rcmail::Q($this->gettext('new_application_step1_legend'))) .
                               html::p(null, rcmail::Q($this->gettext('new_application_step1_description'))) .
                               $table->show()
                           )
                       )
                   ); 
        

        $rcmail->output->add_gui_object('new_application_form', 'new_application_form');

        // This creates the list of existing application specific passwords
        $table = new html_table(array('id' => 'existing_applications', 'class' => 'propform', 'cellspacing' => '0', 'cols' => 4));
        $table->add_header(array('class' => 'title', 'style' => 'width: 40%'), '<b>' . rcmail::Q($this->gettext('applications')) . '</b>');
        $table->add_header(array('class' => 'title', 'style' => 'width: 20%'), '<b>' . rcmail::Q($this->gettext('date_created')) . '</b>');
		$table->add_header(array('class' => 'title', 'style' => 'width: 20%'), '<b>' . rcmail::Q($this->gettext('date_last_used')) . '</b>');
        $table->add_header(array('class' => 'title', 'style' => 'width: 20%'), '&nbsp;');

        // REPLACE WITH SQL QUERY CODE
        $applications = $this->_get_applications();

        foreach ($applications as $application)
        {
			$onclick='';
			if ($application['lastlogin']){
				$onclick = 'return confirm("'.$this->gettext('delete_warning').'")';
			}
            $link_delete = html::tag('a', array('onclick'=>$onclick,'href' => '?_task=settings&_action=plugin.application_passwords-delete&appid=' .  $application['app_id']), rcmail::Q($this->gettext('delete')));
            $table->add(array('style' => 'width: 40%'), $application['application']);
            $table->add(array('style' => 'width: 20%'), $application['created']);
			$table->add(array('style' => 'width: 20%'), $application['lastlogin']);
            $table->add(array('style' => 'width: 20%'), $link_delete);
        }

        $section_existing = html::div(array('id' => 'existing_application_passwords', 'class' => 'boxcontent propform'),
                                html::tag('fieldset', null, html::tag('legend', null, rcmail::Q($this->gettext('existing_application_passwords'))) . $table->show())
                            );

        return ($title . $section_new . $section_existing);
    }


    function application_passwords_step2() 
    {
        $rcmail = rcmail::get_instance();

        $title = html::tag('h1',array('class' => 'boxtitle'), rcmail::Q($this->gettext('application_passwords')));

        $output = html::div(array('id' => 'existing_application_passwords', 'class' => 'boxcontent'),
                      $rcmail->output->form_tag(array('id' => 'application_confirmation_form', 'class' => 'propform', 'name' => 'application_confirmation_form', 'method' => 'post', 'action' => './?_task=settings&_action=plugin.application_passwords'),
                          html::tag('fieldset', null, html::tag('legend', null, rcmail::Q($this->gettext('new_application_step2_legend'))) .
                              html::p(null, rcmail::Q($this->gettext('new_application_step2_description'))) .
							  html::div(null, 
								html::p(array('style' => 'text-align: center; font-size: 200%; letter-spacing: 2px; font-family: monospace; background-color: #efefef; padding: 5px;'), $this->password)  
							  ) .
                              html::tag('input', array('type' => 'submit', 'id' => '', 'class' => 'button mainaction', 'value' => rcmail::Q($this->gettext('back'))))
                          )
                      )
                  );
        $rcmail->output->add_gui_object('application_confirmation_form', 'application_confirmation_form');
 
        return ($title . $output);
    }

    private function _get_applications()
    {
        $rcmail = rcmail::get_instance();
        if (!($sql = $rcmail->config->get('application_passwords_select_query'))) {
            return False;
        }

        if ($dsn = $rcmail->config->get('application_passwords_db_dsn')) {
            // #1486067: enable new_link option
            if (is_array($dsn) && empty($dsn['new_link']))
                $dsn['new_link'] = true;
            else if (!is_array($dsn) && !preg_match('/\?new_link=true/', $dsn))
                $dsn .= '?new_link=true';

            $db = rcube_db::factory($dsn, '', true);
            $db->db_connect('r');
        }
        else {
            $db = $rcmail->get_dbh();
        }

        if ($db->is_error()) {
            return False;
        }

		$user_id = $rcmail->user->ID;
        $local_part  = $rcmail->user->get_username('local');
        $domain_part = $rcmail->user->get_username('domain');

		$sql = $this->_parse_sql($db, $sql, '%u', $user_id);
        // at least we should always have the local part
        $sql = $this->_parse_sql($db, $sql, '%l', $local_part);
        $sql = $this->_parse_sql($db, $sql, '%d', $domain_part);
        
        $sql_result = $db->query($sql);

        $applications = [];
        while ($sql_array = $db->fetch_assoc($sql_result)) {
            $applications[] = $sql_array;
        }

        return($applications);

    }

    private function _save($application, $password)
    {    
        $rcmail = rcmail::get_instance();
       
        if (!($sql = $rcmail->config->get('application_passwords_insert_query'))) {
            return False;
        }

        if ($dsn = $rcmail->config->get('application_passwords_db_dsn')) {
            // #1486067: enable new_link option
            if (is_array($dsn) && empty($dsn['new_link']))
                $dsn['new_link'] = true;
            else if (!is_array($dsn) && !preg_match('/\?new_link=true/', $dsn))
                $dsn .= '?new_link=true';

            $db = rcube_db::factory($dsn, '', true);
            $db->db_connect('w');
        }
        else {
            $db = $rcmail->get_dbh();
        }

        if ($db->is_error()) {
            return False;
        }
		$user_id = $rcmail->user->ID;
        $local_part  = $rcmail->user->get_username('local');
        $domain_part = $rcmail->user->get_username('domain');
	
		//Generate an 8 byte salt
		//TODO: Make this a config setting (length)
		$salt = bin2hex(openssl_random_pseudo_bytes(8));
		        
		$sql = $this->_parse_sql($db, $sql, '%u', $user_id);
		// at least we should always have the local part
        $sql = $this->_parse_sql($db, $sql, '%l', $local_part);
        $sql = $this->_parse_sql($db, $sql, '%d', $domain_part);
        $sql = $this->_parse_sql($db, $sql, '%p', str_replace(' ','',$password));
		$sql = $this->_parse_sql($db, $sql, '%s', $salt);
        $sql = $this->_parse_sql($db, $sql, '%a', $application);
 
        $res = $db->query($sql);

        if (!$db->is_error()) {
            if (strtolower(substr(trim($sql),0,6)) == 'select') {
                if ($db->fetch_array($res))
                    return True;
            } else {
                // This is the good case: 1 row updated
                if ($db->affected_rows($res) == 1)
                    return True;
                // @TODO: Some queries don't affect any rows
                // Should we assume a success if there was no error?
            }
        }

        return false;
    }

    private function _delete($appid)
    {    
        $rcmail = rcmail::get_instance();
       
        if (!($sql = $rcmail->config->get('application_passwords_delete_query'))) {
            return False;
        }

        if ($dsn = $rcmail->config->get('application_passwords_db_dsn')) {
            // #1486067: enable new_link option
            if (is_array($dsn) && empty($dsn['new_link']))
                $dsn['new_link'] = true;
            else if (!is_array($dsn) && !preg_match('/\?new_link=true/', $dsn))
                $dsn .= '?new_link=true';

            $db = rcube_db::factory($dsn, '', true);
            $db->db_connect('w');
        }
        else {
            $db = $rcmail->get_dbh();
        }

        if ($db->is_error()) {
            return False;
        }

		$user_id = $rcmail->user->ID;
        $local_part  = $rcmail->user->get_username('local');
        $domain_part = $rcmail->user->get_username('domain');

        // at least we should always have the local part
		$sql = $this->_parse_sql($db, $sql, '%u', $user_id);
        $sql = $this->_parse_sql($db, $sql, '%l', $local_part);
        $sql = $this->_parse_sql($db, $sql, '%d', $domain_part);
        $sql = $this->_parse_sql($db, $sql, '%p', $password);
        $sql = $this->_parse_sql($db, $sql, '%a', $application);
		$sql = $this->_parse_sql($db, $sql, '%i', $appid);
 
        $db->query($sql);

        if (!$db->is_error()) {
            if (strtolower(substr(trim($sql),0,6)) == 'select') {
                if ($db->fetch_array($res))
                    return True;
            } else {
                // This is the good case: 1 row updated
                if ($db->affected_rows($res) == 1)
                    return True;
                // @TODO: Some queries don't affect any rows
                // Should we assume a success if there was no error?
            }
        }

       return False;
    }

    private function _parse_sql($db, $sql, $var, $val)
    {
        $rcmail = rcmail::get_instance();
		
		// Handle clear text values
        if ( ($var == '%p') && (strpos($sql, '%p') !== FALSE) ) {
            $sql = str_replace('%p', $db->quote($val, 'text'), $sql);
        }
		
		if ( ($var == '%s') && (strpos($sql, '%s') !== FALSE) ) {
            $sql = str_replace('%s', $db->quote($val, 'text'), $sql);
        }

        if ( ($var == '%l') && (strpos($sql, '%l') !== FALSE) ) {
            $sql = str_replace('%l', $db->quote($val, 'text'), $sql);
        }
		
		if ( ($var == '%i') && (strpos($sql, '%i') !== FALSE) ) {
            $sql = str_replace('%i', $db->quote($val, 'int'), $sql);
        }
						
		if ( ($var == '%u') && (strpos($sql, '%u') !== FALSE) ) {
            $sql = str_replace('%u', $db->quote($val, 'int'), $sql);
        }

        if ( ($var == '%d') && (strpos($sql, '%d') !== FALSE) ) {
            // convert domains to/from punnycode
            if ($rcmail->config->get('application_passwords_idn_ascii')) {
                $domain_part = rcube_utils::idn_to_ascii($val);
            }
            else {
                $domain_part = rcube_utils::idn_to_utf8($val);
            }
            $sql = str_replace('%d', $db->quote($domain_part, 'text'), $sql);
        }

        if ( ($var == '%a') && (strpos($sql, '%a') !== FALSE) ) {
            $sql = str_replace('%a', $db->quote($val, 'text'), $sql);
        }

        return $sql;
    }

    private function _password()
    {
		$random = bin2hex(openssl_random_pseudo_bytes(8));
        for ($c = 0; $c < strlen($random); $c++) {
            $password .= $random[$c];
			if (($c+1) % 4 === 0) {
				$password .= ' ';
			}
        }
        return $password;
    }
}

?>
