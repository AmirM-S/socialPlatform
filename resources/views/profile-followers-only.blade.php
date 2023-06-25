<div class="list-group">
    @foreach ($followers as $follower)
    <a href="/profile/{{$follower->userDoingTheFollow->username}}" class="list-group-item list-group-item-action">
        <img class="avatar-tiny" src="{{$follower->userDoingTheFollow->avatar}}" />
        {{$follower->userDoingTheFollow->username}}
    </a>
    @endforeach
</div>