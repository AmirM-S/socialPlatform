<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Follow;
use App\Events\ExampleEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class userController extends Controller
{
    public function storeAvatar(Request $request) {
        $request->validate([
            'avatar' => 'required|image|max:5000'
        ]);

        $user = auth()->user();

        $fileName = $user->id . '-' . uniqid() . '.jpg';

        $imageData = Image::make($request->file('avatar'))->fit(120)->encode('jpg');
        Storage::put('public/avatars/' . $fileName, $imageData);

        $oldAvatar = $user->avatar;

        $user->avatar = $fileName;
        $user->save();

        if ($oldAvatar != "/fallback-avatar.jpg") {
            Storage::delete(str_replace("/storage/", "public/", $oldAvatar));   
        }

        return back()->with('success', 'avatart picture has been updated.');
    }

    public function showAvatarForm() {
        return view('avatar-form');
    }

    private function getSharedData($user) {
        $currentFollow = 0;
        if(auth()->check()) {
            $currentFollow = Follow::where([['user_id', '=', auth()->user()->id],['followeduser', '=', $user->id]])->count();
        }
        View::share('sharedData', ['currentFollow' => $currentFollow, 'avatar' => $user->avatar, 'username' => $user->username, 'postCount' => $user->posts()->count(), 'followerCount' => $user->follower()->count(), 'followingCount' => $user->followingTheseUsers()->count()]);
    }

    public function profile(User $user) {
        $this->getSharedData($user);
        return view('profile-posts', ['posts' => $user->posts()->latest()->get()]);
    }

    public function profileRaw(User $user) {
        return response()->json(['theHTML' => view('profile-posts-only', ['posts' => $user->posts()->latest()->get()])->render(), 'docTitle' => $user->username . "'s profile"]);
    }

    public function profileFollowers(User $user) {
        $this->getSharedData($user);
        return view('profile-followers', ['followers' => $user->follower()->latest()->get()]);
    }

    public function profileFollowersRaw(User $user) {
        return response()->json(['theHTML' => view('profile-followers-only', ['followers' => $user->follower()->latest()->get()])->render(), 'docTitle' => $user->username . "'s followers"]);
    }

    public function profileFollowings(User $user) {
        $this->getSharedData($user);
        return view('profile-followings', ['followings' => $user->followingTheseUsers()->latest()->get()]);
    }

    public function profileFollowingsRaw(User $user) {
        return response()->json(['theHTML' => view('profile-followings-only', ['followings' => $user->followingTheseUsers()->latest()->get()])->render(), 'docTitle' => "who " . $user->username . " follow"]);
    }

    public function logout() {
        event(new ExampleEvent(['username' => auth()->user()->username, 'action' => 'logout']));
        auth()->logout();
        return redirect('/')->with('success', 'You are now logged out.');
    }

    public function correctHomepage() {
        if (auth()->check()) {
            return view('homepage-feed', ['posts' => auth()->user()->feedPosts()->latest()->paginate(2)]);
        } else {

            $postCount = Cache::remember('postCount', 5, function() {
                sleep(5);
                return Post::count();
            });
            return view('homepage', ['postCount' => $postCount]);
        }
    }

    public function login(Request $request) {
        $incomingFields = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required'
        ]);

        if (auth()->attempt(['username' => $incomingFields['loginusername'], 'password' => $incomingFields['loginpassword']])) {
            $request->session()->regenerate();
            event(new ExampleEvent(['username' => auth()->user()->username, 'action' => 'login']));
            return redirect('/')->with('success', 'You have successfully logged.');
        } else {
            return redirect('/')->with('failure', 'Invalid login.');
        }
    }

    public function loginApi(Request $request) {
        $incomingFields = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);
        if (auth()->attempt($incomingFields)) {
            $user = User::where('username', $incomingFields['username'])->first();
            $token = $user->createToken('myapptoken')->plainTextToken;
            return $token;
        }
        return 'sorry';
    }


    public function register(Request $request) {
        $incomingFields = $request->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:8', 'confirmed']
        ]);

        $incomingFields['password'] = bcrypt($incomingFields['password']);
        
        $user = User::create($incomingFields);
        auth()->login($user);
        return redirect('/')->with('success', 'Thank you for creating an account.');
    }
}
