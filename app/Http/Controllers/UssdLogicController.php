<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sessions;
use africastalking\SDK\africastalking;
class UssdLogicController extends Controller
{
    /**
     * @var | mixed
     * get post request from AT
     */
    private $session_id;
    private $service_code;
    private $phone_number;
    private $text;

    private $AT_username;
    private $AT_apikey;
    private $AT;//africastalking instance

    protected string $response;
    protected int $level;
    protected $textArray;
    protected $user_response;

    public function __construct(Request $request)
    {
        /**
         * initialise values when the POST request
         * is not empty from AT
         */
        if(!empty($request->all())){

            $this->session_id   =$request->get('sessioId');
            $this->service_code =$request->get('serviceCode');
            $this->phone_number =$request->get('phoneNumber');
            $this->text         =$request->get('text');
        }

        $this->AT_apikey    =env('AT_API_KEY');
        $this->AT_username  =env('AT_USERNAME');
        $this->AT=new africastalking($this->AT_username,$this->AT_apikey);

        //initialise with default values
        $this->level    =0;
        $this->response ="";

        $this->textArray=explode("*",$this->text);

        $this->user_response=trim(end($this->textArray));
    }

    public function register_user(){
        /**
         * Check the level of the user from the
         * DB and retain default level if none
         * is found for this session
         */
        $level_query=Sessions:: where('session_id',$this->session_id)
                                ->pluck('level')
                                ->first();
        $level_query   ?   $this->level=$level_query    :   $this->level;

        /**
         * check if the user
         * is in the db
         */
        $user=User::where('phoneNumber','like','%'.$this->phone_number.'%')
                    ->get(['username','city']);

        /**
         * Check if the user is available (yes)->Serve the menu;
         * (no)->Register the user
         */
        if($user){
            /**
             *Serve the Services Menu
             *Check that the user actually typed something,
             *else demote level and start at home
             */
            $username=User::where('phone_number',$this->phone_number)->pluck('username')->all();

            switch ($this->user_response){
                case "" :
                    if($this->level==0){
                        //Graduate user to next level & Serve Main Menu
                        Sessions::create([
                            'session_id'    =>$this->session_id,
                            'phone_number'  =>$this->phone_number,
                            'level'         =>1
                        ]);
                        //Serve our services menu

                        $this->response="Karibu $username,please choose a service";
                        $this->response.="1.Send me today's voice tip\n";
                        $this->response.="2.Please call me!\n";
                        $this->response.="3.Send me airtime";

                        // Print the response onto the page so that our gateway can read it
                        header('Content-Type:text/plain');
                        $this->ussd_proceed($this->response);
                    }
                    break;


                case "1"    :

                    if($this->level==1){
                        // Send the user todays voice tip via AT SMS API


                    }

            }
        }
    }
    public function ussd_proceed($proceed){
        echo "CON $proceed";
    }
    public function ussd_finish($stop){
        echo "END $stop";
    }














}
