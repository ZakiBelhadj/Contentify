<?php namespace App\Modules\Teams\Controllers;

use App\Modules\Teams\Models\Team;
use Input, Response, View, HTML, DB, User, BackController;

class AdminMembersController extends BackController {

    protected $icon = 'group.png';

    public function __construct()
    {
        $this->model = '\User';

        parent::__construct();
    }

    public function getIndex()
    {
        $this->indexPage([
            'buttons'   => null,
            'tableHead' => [
                trans('app.id')         => 'id', 
                trans('app.username')   => 'username',
                trans('users::teams')   => null,
            ],
            'tableRow' => function($user)
            {
                $data = [];
                foreach ($user->teams as $team) {
                    $data[$team->id] = $team->title;
                }

                return [
                    $user->id,
                    $user->username,
                    '<div class="data" data-user-id="'.$user->id.'">'.json_encode($data).'</div>',
                ];            
            },
            'searchFor' => 'username',
            'actions'   => null
        ]);
        $this->pageOutput(HTML::script('libs/members.js'));
    }

    public function deleteDelete($userId, $teamId)
    {
        $user = User::findOrFail($userId);

        DB::table('team_user')->whereUserId($userId)->whereTeamId($teamId)->delete();

        $data = [];
        foreach ($user->teams as $team) {
            $data[$team->id] = $team->title;
        }

        return json_encode($data);
    }

    public function getAdd($userId)
    {
        $ids = DB::table('team_user')->whereUserId($userId)->lists('team_id');

        if (sizeof($ids) > 0) {
            $teams = Team::whereNotIn('id', $ids)->get();
        } else {
            $teams = Team::all();
        }
            
        if (sizeof($teams) > 0) {
            return View::make('teams::admin_members_team', compact('teams', 'user'));
        } else {
            return Response::make('');
        }       
    }

    public function postAdd($userId, $teamId)
    {
        $user = User::findOrFail($userId);

        DB::table('team_user')->insert([
            'team_id'       => $teamId, 
            'user_id'       => $userId,
        ]);

        $data = [];
        foreach ($user->teams as $team) {
            $data[$team->id] = $team->title;
        }

        return json_encode($data);
    }

    public function getEdit($userId, $teamId)
    {
        $user = User::findOrFail($userId);

        foreach ($user->teams as $team) {
            if ($team->id == $teamId) {
                return View::make('teams::admin_members_edit', compact('team'));
            }
        }
        
        return Response::make(0);
    }

    public function postUpdate($userId, $teamId)
    {
        $task           = Input::get('task');
        $description    = Input::get('description');
        $position       = (int) Input::get('position');

        DB::table('team_user')->whereUserId($userId)->whereTeamId($teamId)->update([
            'task'          => $task,
            'description'   => $description,
            'position'      => $position,
        ]);

        return Response::make(1);
    }

}