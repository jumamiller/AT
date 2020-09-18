<?php

namespace App\Http\Controllers;

use Dompdf\Exception;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sessions;
use AfricasTalking\SDK\AfricasTalking;

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
    private AfricasTalking $AT;

    protected $response;
    protected int $level;
    protected $textArray;
    protected $user_response;
    protected string $header="Content-type:text/plain";

    public function __construct(Request $request)
    {
        /**
         * initialise values when the POST request
         * is not empty from AT
         */
        if(!empty($request->all())){

            $this->session_id   =$request->get('sessionId');
            $this->service_code =$request->get('serviceCode');
            $this->phone_number =$request->get('phoneNumber');
            $this->text         =$request->get('text');
        }

        $this->AT_apikey    =env('AT_API_KEY');
        $this->AT_username  =env('AT_USERNAME');
        $this->AT           =new AfricasTalking($this->AT_username,$this->AT_apikey);

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
        $level_query=Sessions:: where('session_id',$this->session_id)->pluck('level')->first();
        $level_query   ?   $this->level=$level_query    :   $this->level;

        /**
         * check if the user
         * is in the db
         */
        $user=User::where('phone_number','like','%'.$this->phone_number.'%')
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
                        $current_user=implode("",$username);

                        $this->response="Karibu $current_user,please choose a service\n";
                        $this->response.="1.Send me today's voice tip\n";
                        $this->response.="2.Please call me!\n";
                        $this->response.="3.Send me airtime";

                        // Print the response onto the page so that our gateway can read it
                        $this->header;
                        $this->ussd_proceed($this->response);
                    }
                    break;


                case "1"    :

                    if($this->level==1){
                        // Send the user todays voice tip via AT SMS API
                        $this->response ="Please check your SMS inbox";
                        $short_code     =18954;
                        $recipients     =$this->phone_number;
                        $message        ="My first africastalking ussd and sms is working";

                        try{
                            $sms=$this->AT->sms();

                            $sms->send([
                                'to'        =>$recipients,
                                'message'   =>$message,
                                'from'      =>$short_code
                            ]);

                        }catch(\Exception $e ){
                            echo("Error while sending sms ".$e->getMessage());
                        }
                        $this->header;
                        $this->ussd_finish($this->response);
                    }
                    break;

                case "2"    :
                    if($this->level==1){
                        //Call the user and bridge to a sales person
                        $this->response="Please wait while we place your call.\n";
                        //make a call
                        $from   ="+254717720862";
                        $to     =$this->phone_number;

                        $this->AT->call($from,$to);
                        $this->header;
                        $this->ussd_finish($this->response);
                    }
                    break;
                case "3"    :
                    if($this->level==1){
                        /**
                         * send user airtime
                         */
                        $this->response="Please wait as we load your account";
                        /**
                         * search db and send airtime
                         */
                        $parameters=[
                            'recipients'=>[[
                                'phoneNumber'   =>$this->phone_number,
                                'currencyCode'  =>'KES',
                                'amount'        =>'10'
                            ]]
                        ];
                        $airtime=$this->AT->airtime();
                        $airtime->send($parameters);

                        $this->header;
                        $this->ussd_finish($this->response);
                    }
                    break;
                default:
                    if($this->level==1){
                        // Return user to Main Menu & Demote user's level
                        $this->response="You have to choose a service\n";
                        $this->response.="Press 0 to go back";

                        Sessions::where('session_id',$this->session_id)->update(['level'=>0]);

                        $this->header;
                        $this->ussd_proceed($this->response);
                    }
            }
        }
        else{
                /**
                 * run this stmts if the user doesnt exists
                 * On receiving a Blank. Advise user
                 * to input correctly based on level
                 */

                if($this->user_response==""){
                    switch ($this->level){
                        case 0:
                            //Graduate the user to the next level, so you dont serve them the same menu
                            Sessions::create([
                                'session_id'    =>$this->session_id,
                                'phone_number'  =>$this->phone_number,
                                'level'         =>1
                            ]);
                            User::create([
                                'phone_number'  =>$this->phone_number
                            ]);
                            $this->response="Please enter your name";
                            $this->header;
                            $this->ussd_proceed($this->response);

                            break;

                        case 1:
                            //request again for name
                            $this->response="Name not supposed to be empty. Please enter your name \n";
                            $this->header;
                            $this->ussd_proceed($this->response);
                            break;

                        case 2:
                            //request for city again
                            
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
