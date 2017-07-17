<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Session;

use App\Event;
use App\EventMember;
use App\EventPrice;
use App\Club;
use App\Contact;
use App\Role;
use App\Roleship;
use App\TransactionForEvent;
use App\User;

class EventController extends Controller{

    public function addPrice(Request $request){
        $ePrice = new EventPrice;

        if( $request -> pName != '' ){
            $ePrice -> name = $request -> pName;
            if( $request -> pDesc != '' ){
                $ePrice -> description = $request -> pDesc;
                if( $request -> pCost != '' ){
                    $ePrice -> cost = $request -> pCost;
                    if( $request -> pMO != '' ){
                        $price_isMemberOnly = 1;
                    }else{
                        $price_isMemberOnly = 0;
                    }
                    $ePrice -> members_only = $price_isMemberOnly;
                    $ePrice -> event_id = Session::get('eventId');
                    $ePrice -> timestamps = false;
                    $ePrice -> save();
                    return back()
                        -> with('active_tab', $request -> active_tab);
                }else{
                    return back()
                        -> with('active_tab', $request -> active_tab)
                        -> with('plan_msg', 'price cost missed');}
            }else
                return back()
                    -> with('active_tab', $request -> active_tab)
                    -> with('plan_msg', 'price description missed');
        }else{
            return back()
                -> with('active_tab', $request -> active_tab)
                -> with('plan_msg', 'price name missed');
        }
    }

    public function createEvent(Request $request){

        if( $request -> hasFile('event_logo') ){
            $this -> validate($request, [
                'event_logo' => 'required|image|mimes:jpeg,png,jpg',
            ]);
            $logoName = time().'.'.$request -> event_logo -> getClientOriginalExtension();
            $request -> file('event_logo') -> move(public_path('uploads/images'), $logoName);
            $imagePath = asset('uploads/images')."/".$logoName;
        }
        else{
            $imagePath = asset('uploads/images')."/".'event.png';
        }

        $slug = Event::where('slug', '=', $request -> input( 'event_slug' ))->first();
        if ($slug !== null) {
            return back()
                -> with('message', 'The slug already exist. Failed to create the event.');
        }

        {
            $contact = new Contact;
            $contact -> zipcode = $request -> input( 'event_zipcode' );
            $contact -> city = $request -> input( 'event_city' );
            $contact -> state = $request -> input( 'event_state' );
            $contact -> save();

            $event = new Event;
            $event -> name = $request -> input( 'event_name' );
            $event -> slug = $request -> input( 'event_slug' );
            $event -> logo_path = $imagePath;
            $event -> description = $request -> input( 'event_description' );
            $event -> short_description = $request -> input( 'event_short_description' );
            $event -> start_date = $request -> input( 'event_start' );
            $event -> end_date = $request -> input( 'event_end' );
            $event -> access = $request -> input( 'event_access' );
            $event -> club_id = session('theClubID');
            $event -> creater_user_id = Auth::id();
            $memberLimit = $request -> input( 'event_memberlimit' );
            if( null == $memberLimit ) $memberLimit = 9999;
            $event -> member_limit = $memberLimit;
            $event -> contact_id = $contact -> id;
            if( 'on' == $request -> input( 'disp_guest' ) ){
                $event -> guest_display =  1;
            }else{
                $event -> guest_display =  0;
            }
            $event -> save();

            return back()
                -> with('message', 'Welcome! Event created successfully.');
        }
    }

    public function configureEvent(Request $request){

        $event = Event::find(Session::get('eventId'));
        $contact = Contact::find($event -> contact_id);
        if( $request -> input( 'event_name' ) != '' ){
            $event -> name = $request -> input( 'event_name' );
        }else
            echo 'name wrong';
        if( $request -> input( 'event_slug' ) != '' ){
            $event -> slug = $request -> input( 'event_slug' );
        }else
            echo 'slug wrong';
        if( $request -> input( 'event_desc_pub' ) != '' ){
            $event -> description = $request -> input( 'event_desc_pub' );
        }else
            echo 'pub wrong';
        if( $request -> input( 'event_desc_prv' ) != '' ){
            $event -> short_description = $request -> input( 'event_desc_prv' );
        }else
            echo 'prv wrong';

        if( $request -> input( 'event_access' ) != '' ){
            $event -> access = $request -> input( 'event_access' );
        }else
            echo 'type wrong';
        if( $request -> input( 'zip_code' ) ){
            $contact -> zipcode = $request -> input( 'zip_code' );
        }else
            echo 'zcod wrong';

        if( $request -> hasFile('event_logo') ){
            $this -> validate($request, [
                'event_logo' => 'required|image|mimes:jpeg,png,jpg',
            ]);
            $logoName = time().'.'.$request -> event_logo -> getClientOriginalExtension();
            $request -> file('event_logo') -> move(public_path('uploads/images'), $logoName);
            $imagePath = asset('uploads/images/')."/".$logoName;
            $event -> event_path = $imagePath;
        }
        $contact -> save();
        $event -> save();

        return back()
//            -> withErrors('msg', 'The Message')
            -> with('active_tab', $request -> active_tab)
            ;
    }

    public function editContact(Request $request){

        $contact = Contact::find(Event::find(session('eventId')) -> contact_id);

        if( '' != $request -> use_club ){
            $clubContact = Contact::find(Club::find(Event::find(session('eventId')) -> club_id));
            $contact -> city = $clubContact -> city;
            $contact -> state = $clubContact -> state;
            $contact -> zipcode = $clubContact -> zipcode;
            $contact -> pcm_id = $clubContact -> pcmid;
            $contact -> scm_id = $clubContact -> scmid;
            $contact -> linkedin = $clubContact -> inLink;
            $contact -> level_in = $clubContact -> inLevel;
            $contact -> twitter = $clubContact -> ttLink;
            $contact -> level_t = $clubContact -> ttLevel;
            $contact -> facebook = $clubContact -> fbLink;
            $contact -> level_f = $clubContact -> fbLevel;
            $contact -> youtube = $clubContact -> ytLink;
            $contact -> level_y = $clubContact -> ytLevel;
            $contact -> googleplus = $clubContact -> goLink;
            $contact -> level_g = $clubContact -> goLevel;
            $contact -> mail = $clubContact -> maLink;
            $contact -> level_m = $clubContact -> maLevel;
        }
        else{
            $contact -> city = $request -> city;
            $contact -> state = $request -> state;
            $contact -> zipcode = $request -> zipcode;
            $contact -> pcm_id = $request -> pcmid;
            $contact -> scm_id = $request -> scmid;
            $contact -> linkedin = $request -> inLink;
            $contact -> level_in = $request -> inLevel;
            $contact -> twitter = $request -> ttLink;
            $contact -> level_t = $request -> ttLevel;
            $contact -> facebook = $request -> fbLink;
            $contact -> level_f = $request -> fbLevel;
            $contact -> youtube = $request -> ytLink;
            $contact -> level_y = $request -> ytLevel;
            $contact -> googleplus = $request -> goLink;
            $contact -> level_g = $request -> goLevel;
            $contact -> mail = $request -> maLink;
            $contact -> level_m = $request -> maLevel;
        }

        $contact -> save();

        return back()
            -> with('active_tab', $request -> active_tab);
    }

    public function editPrice(Request $request){
        if ($request->price_id != '') {

            $ePrice = EventPrice::find($request->price_id);

            if( $request -> pName != '' ){
                $ePrice -> name = $request -> pName;
                if( $request -> pDesc != '' ){
                    $ePrice -> description = $request -> pDesc;
                    if( $request -> pCost != '' ){
                        $ePrice -> cost = $request -> pCost;
                        if( $request -> pMO != '' ){
                            $plan_isMemberOnly = 1;
                        }else{
                            $plan_isMemberOnly = 0;
                        }
                        $ePrice -> members_only = $plan_isMemberOnly;
                        $ePrice -> timestamps = false;
                        $ePrice -> save();
                        return back()
                            -> with('active_tab', $request -> active_tab);
                    }else
                        return back()
                            -> with('active_tab', $request -> active_tab)
                            -> with('plan_msg', 'price cost missed');
                }else
                    return back()
                        -> with('active_tab', $request -> active_tab)
                        -> with('plan_msg', 'price description missed');
            }else{
                return back()
                    -> with('active_tab', $request -> active_tab)
                    -> with('plan_msg', 'price name missed');
            }

        } else
            return back()
                -> with('active_tab', $request -> active_tab)
                -> with('plan_msg', 'unexpected exception with price ID');
    }

    public function eventCreate(){

        return view('event/createEvent')->with('page', 'createEvent');
    }

    public function eventManagement($slug){

        $event = Event::where('slug', '=', $slug) -> first();
        session(['eventId' => $event -> id]);

        $club = Club::find($event -> club_id);

        $isAlreadyEventMember = EventMember::where('event_id', $event -> id)
            -> where('user_id', Auth::id())
            -> first();
        $role = NULL;
        $eventPrices = NULL;

        $roleship = Roleship::where('user_id', Auth::id()) -> where('club_id', $event -> club_id) -> first();
        if(!is_null($roleship)){
            $role = Role::find($roleship -> role_id) -> role_description;
        }

        if(is_null($isAlreadyEventMember)){
            Session::set('stripe_secret_key', $club -> stripe_pvt_key);

            if('owner' == $role || 'admin' == $role){
                $eventPrices = EventPrice::where('event_id', $event->id)
                    -> select('*')
                    -> get();
            }elseif('member' == $role) {
                $eventPrices = EventPrice::where('event_id', $event->id)
                    -> where('members_only', '1')
                    ->select('*')
                    ->get();
            }else{
                $eventPrices = EventPrice::where('event_id', $event->id)
                    -> where('members_only', '0')
                    -> select('*')
                    -> get();
            }
        }else{
            Session::set('stripe_secret_key', NULL);
        }

        $theContact = Contact::find($event -> contact_id);

        $pcm_id = $theContact -> pcm_id;
        $scm_id = $theContact -> scm_id;

        if($pcm_id != '' && $pcm_id != 'None'){
            $thePCM = User::find($pcm_id);
            $thePCMRoleID = Roleship::where('user_id', $pcm_id) -> where('club_id', $event -> club_id) -> first() -> role_id;
            $thePCMRole = Role::find($thePCMRoleID) -> role_description;
        }
        else{
            $thePCM = NULL;
            $thePCMRole = NULL;
        }

        if($scm_id != '' && $scm_id != 'None'){
            $theSCM = User::find($scm_id);
            $theSCMRoleID = Roleship::where('user_id', $scm_id) -> where('club_id', $event -> club_id) -> first() -> role_id || NULL;
            $theSCMRole = Role::find($theSCMRoleID) -> role_description;
        }else{
            $theSCM = NULL;
            $theSCMRole = NULL;
        }

        $eventMembers = DB::table('event_members')
            -> where('event_id', '=', $event -> id)
            -> join('users', 'users.id', '=', 'event_members.user_id')
            -> select('users.id', 'users.email', 'users.first_name', 'users.last_name', 'users.profile_image', 'event_members.invited', 'event_members.created_at as invite_date', 'event_members.updated_at as accept_date')
            -> get();
        $trForEvent = DB::table('transaction_for_cevent')
            ->where('transaction_for_cevent.event_id', $event -> id)
            ->join('event_members', 'transaction_for_cevent.event_id', '=', 'event_members.event_id')
            ->join('users', 'transaction_for_cevent.user_id', '=', 'users.id')
            ->select('transaction_for_cevent.date', 'users.first_name', 'users.last_name', 'transaction_for_cevent.amount', 'transaction_for_cevent.source', 'transaction_for_cevent.receipt')
            ->get();

        return view('event/eventManagement', [
            'page' => 'Event Management',
            'isAlreadyEventMember' => $isAlreadyEventMember,
            'event' => $event,
            'theUserRole' => $role,
            'theClub' => $club,
            'eventPrices' => $eventPrices,
            'stripe_public_key' => $club -> stripe_pub_key,
            'theContact' => $theContact,
            'thePCM' => $thePCM,
            'thePCMRole' => $thePCMRole,
            'theSCM' => $theSCM,
            'theSCMRole' => $theSCMRole,
            'eventMembers' => $eventMembers,
            'transForEvent' => $trForEvent
        ]);
    }

    public function getEventDates(Request $request){
        //echo 'here';
        $eventDate = json_encode(array(
            array(
                'title'=>'test',
                'start'=>'2017-07-02T12:00:00',
                'end'=>'2017-07-03T13:00:00'
            )
        ));
        echo $eventDate;
    }

    public function inviteAMember(Request $request){

        $isExist = User::where('first_name', $request -> input( 'first_name' )) ->
        where('last_name', $request -> input( 'last_name' )) ->
        where('email', $request -> input( 'email' )) -> count();

        if( $isExist > 0 ){

            $user = User::where('first_name', $request -> input( 'first_name' )) ->
            where('last_name', $request -> input( 'last_name' )) ->
            where('email', $request -> input( 'email' )) -> first();

            if($user -> id == Auth::id())
            {
                return back()
                    -> with('active_tab',  $request -> active_tab)
                    -> with('members_msg', 'can not invite yourself');
            }
            else{
                $eventMembers = new EventMember;
                $eventMembers -> user_id = $user -> id ;
                $eventMembers -> event_id = session('eventId');
                $eventMembers -> invited = 1;
                $eventMembers -> save();
                return back() -> with('active_tab',  $request -> active_tab);
            }
        }
        else{
            return back()
                -> with('active_tab',  $request -> active_tab)
                -> with('members_msg', 'the user does not exist');
        }
    }

    public function showAllEvents(){

        $allEvents = DB::table('events')
            -> join('contacts', 'contacts.id', '=', 'events.contact_id')
            -> join('roleships', 'roleships.club_id', '=', 'events.club_id')
            -> join('users', 'users.id', '=', 'roleships.user_id')
            -> where('events.access', 'Public')
            -> orwhere(function($query){
                $query -> where('events.access', 'Members Only')
                    -> where('roleships.role_id', '>', 1)
                    -> where('roleships.role_id', '<', 5)
                    -> where('users.id',  '=', Auth::id());
            })
            -> orwhere(function($query){
                $query -> where('events.access', 'Private')
                    -> where('roleships.role_id', '>', 1)
                    -> where('roleships.role_id', '<', 4)
                    -> where('users.id',  '=', Auth::id());
            })
            -> select('events.id', 'events.name', 'events.slug', 'events.logo_path', 'events.description', 'events.access', 'contacts.city', 'contacts.state', 'contacts.country')
            -> get()
            -> unique();

        $yourEvents = DB::table('events')
            -> join('contacts', 'contacts.id', '=', 'events.contact_id')
            -> join('clubs', 'clubs.id', '=', 'events.club_id')
            -> join('roleships', 'roleships.club_id', '=', 'clubs.id')
            -> join('users', 'users.id', '=', 'roleships.user_id')
            -> where('users.id', '=', Auth::id())
            -> where(function($query) {
                $query->where('events.access', 'Public')
                    ->where('roleships.role_id', '>', 1)
                    ->where('roleships.role_id', '<', 5);
            })
            -> orwhere(function($query){
                $query -> where('events.access', 'Members Only')
                    -> where('roleships.role_id', '>', 1)
                    -> where('roleships.role_id', '<', 5);
            })
            -> orwhere(function($query){
                $query -> where('events.access', 'Private')
                    -> where('roleships.role_id', '>', 1)
                    -> where('roleships.role_id', '<', 4);
            })
            -> select('events.id')
            -> get()
            -> unique();

        return view('event/allEvents', [
            'page' => 'allEvents',
            'allEvents' => $allEvents,
            'yourEvents' => $yourEvents
        ]);
    }

    public function showMyEvents(){

        $adminEvents = DB::table('events')
            -> join('roleships', 'roleships.club_id', '=', 'events.club_id')
            -> where('roleships.user_id', '=', Auth::id())
            -> where('roleships.role_id', '>', 1)
            -> where('roleships.role_id', '<', 4)
            -> select('events.slug', 'events.name', 'events.logo_path', 'events.created_at')
            -> get()
            -> unique();

        $memberEvents = DB::table('event_members')
            -> where('event_members.user_id', Auth::id())
            -> join('events', 'event_members.event_id', '=', 'events.id')
            -> select('events.slug', 'events.name', 'events.logo_path', 'events.created_at')
            -> get()
            -> unique();

        $myEvents = $adminEvents -> merge($memberEvents);

        return view('event/myEvents', [
            'page' => 'events',
            'myEvents' => $myEvents
        ]);
    }

    public function payForEvent(Request $request){

        if($request -> has('stripeToken')){
            \Stripe\Stripe::setApiKey(Session::get('stripe_secret_key'));

            $token = $request -> stripeToken;

            $charge = \Stripe\Charge::create(array(
                "amount" => 100 * Event_Price::find($request -> ePrice_id) -> cost,
                "currency" => "usd",
                "description" => "Example charge",
                "source" => $token,
            ));

            if($charge -> paid == true){
                $newEventMember = new EventMember;
                $newEventMember -> user_id = Auth::id();
                $newEventMember -> club_id = Event_Member::find($request -> plan_id) -> club_id;
                $newEventMember -> role_id = 4;
                $newEventMember -> save();
                return back();
            }else{
                return back() -> with('msg', 'payment failed');
            }
        }else{
            if( 0 == Membership_plan::find($request -> plan_id) -> cost ){
                return $this -> clubManagement(Club::find( Membership_plan::find($request -> plan_id) -> club_id) -> slug);
            }
        }
    }

}
