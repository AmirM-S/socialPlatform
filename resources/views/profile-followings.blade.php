<x-profile :sharedData="$sharedData" docTitle="who {{$sharedData['username']}}'s Follows">
    @include('profile-followings-only')
  </x-profile>