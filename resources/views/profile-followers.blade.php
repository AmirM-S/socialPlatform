<x-profile :sharedData="$sharedData" docTitle="{{$sharedData['username']}}'s Followers">
    @include('profile-followers-only')
  </x-profile>