<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace auth_twilio;

use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once('vendor/autoload.php');

use Twilio\Rest\Client;

/**
 * Class api
 *
 * @package    auth_twilio
 * @copyright  2024 Wail Abualela <wailabualela@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api {
    /**
     * Service Token
     * @var string
     */
    public string $service;
    /**
     * Plugin configuration
     * @var Client Twilio API Client
     */
    public Client $twilio;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->service = get_config('auth_twilio', 'servicesid');
        $this->twilio  = new Client(get_config('auth_twilio', 'accountsid'), get_config('auth_twilio', 'token'));
    }

    /**
     * Is the plugin enabled.
     *
     * @return bool
     */
    public function is_enabled() {
        return is_enabled_auth('twilio');
    }

    public function verifications($tel) {
        return $this->twilio
            ->verify
            ->v2
            ->services($this->service)
            ->verifications
            ->create($tel, "whatsapp");
    }

    public function verificationChecks($code, $tel) {
        return $this->twilio
            ->verify
            ->v2
            ->services($this->service)
            ->verificationChecks
            ->create([ 'code' => $code, 'to' => $tel ]);
    }

    public function complete_login($data) {
        global $DB;
        if ($this->tel_exist($data['phone'])) {
            $user = $DB->get_record('user', [ 'phone1' => $data['phone'] ]);
        } else {
            $user               = new stdClass();
            $user->phone1       = $data['phone'];
            $user->username     = $data['phone'];
            $user->firstname    = $data['firstname'];
            $user->lastname     = $data['lastname'];
            $user->email        = $data['phone'] . '@gmail.com';
            $user->password     = hash('sha256', $data['phone']);
            $user->auth         = 'twilio';
            $user->confirmed    = 1;
            $user->mnethostid   = 1;
            $user->firstaccess  = time();
            $user->lastaccess   = time();
            $user->lastlogin    = time();
            $user->lastlogin    = time();
            $user->currentlogin = time();
            $user->id           = $DB->insert_record('user', $user);
            $DB->insert_record('user_info_data', [
                'userid'  => $user->id,
                'data'    => $data['fullname'],
                'fieldid' => 1,
            ]);
        }
        complete_user_login($user, []);
        redirect(new moodle_url('/'));
    }

    public function tel_exist($tel) {
        global $DB;
        return $DB->record_exists_sql("SELECT id FROM {user} WHERE phone1 LIKE '%$tel%'");
    }

    public function get_countries_choices() {
        $countries = get_string_manager()->get_list_of_countries();
        $choices = [];
        foreach ($countries as $key => $value) {
            $choices[] = $value;
        }
        return $choices;
    }
}
