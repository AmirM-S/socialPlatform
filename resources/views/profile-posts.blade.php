<x-profile :sharedData="$sharedData" docTitle="{{$sharedData['username']}}'s Profile">
  @include('profile-posts-only')
</x-profile>