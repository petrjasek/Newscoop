{{extends file="layout.tpl"}}

{{block content}}

<div>
    <form method="GET" action="{{ $view->url(['controller' => 'user', 'action' => 'search'], 'default', true) }}">
        <input type="text" name="q"></input>
        <input type="submit" value="search"></input>
    </form>
</div>

<h1>Users index</h1>

<ul class="tabs">
    <li><a href="{{ $view->url(['controller' => 'user', 'action' => 'index'], 'default', true) }}">All</a></li>
    <li><a href="{{ $view->url(['controller' => 'user', 'action' => 'active'], 'default', true) }}">Active</a></li>
    <li><a href="{{ $view->url(['controller' => 'user', 'action' => 'editors'], 'default', true) }}">Editors</a></li>
    <li class="br"></li>
    {{ foreach range('a', 'z') as $character }}
    <li><a href="{{ $view->url(['controller' => 'user', 'action' => 'filter', 'f' => $character], 'default', true) }}">{{ $character|upper }}</a></li>
    {{ /foreach }}
</ul>

<ul class="users">
    {{ foreach $users as $user }}
    <li>
        <h3>{{ $user->uname }}</h3>
        {{ if $user->image }}
        <img src="{{ $user->image }}" />
        {{ /if }}
        <hr />
    </li>
    {{ /foreach }}
</ul>

{{include file='paginator_control.tpl'}}

<div class="community_ticker">
{{ list_community_feeds length=5 }}<p>{{ $gimme->community_feed->created }} {{ $gimme->community_feed->message }}<p>{{ /list_community_feeds }}
</div>

{{/block}}
