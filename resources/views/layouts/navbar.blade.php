<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="{{ route('dashboard') }}">Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        {{-- Ticket --}}
        <li class="nav-item dropdown {{ Request::is('dashboard/ticket*') ? 'active' : '' }}">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Ticket</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item {{ Request::is('dashboard/tickets/create') ? 'active' : '' }}" href="{{ route('tickets.create') }}">Create</a></li>
            <li><a class="dropdown-item {{ Request::is('dashboard/tickets/on-progress') ? 'active' : '' }}" href="{{ route('tickets.on-progress') }}">In Progress</a></li>
            <li><a class="dropdown-item {{ Request::is('dashboard/tickets/report') ? 'active' : '' }}" href="{{ route('tickets.report') }}">Report</a></li>
          </ul>
        </li>

        {{-- Task --}}
        <li class="nav-item dropdown {{ Request::is('dashboard/task*') ? 'active' : '' }}">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Task</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item {{ Request::is('dashboard/task/on-progress') ? 'active' : '' }}" href="{{ route('task.on-progress') }}">In Progress</a></li>
            <li><a class="dropdown-item {{ Request::is('dashboard/task/report') ? 'active' : '' }}" href="{{ route('task.report') }}">Report</a></li>
          </ul>
        </li>

        {{-- Project --}}
        <li class="nav-item dropdown {{ Request::is('dashboard/project*') ? 'active' : '' }}">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Project</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item {{ Request::is('dashboard/project/on-progress') ? 'active' : '' }}" href="{{ route('project.on-progress') }}">In Progress</a></li>
            <li><a class="dropdown-item {{ Request::is('dashboard/project/report') ? 'active' : '' }}" href="{{ route('project.report') }}">Report</a></li>
          </ul>
        </li>

        {{-- User --}}
        <li class="nav-item dropdown {{ Request::is('dashboard/user*') ? 'active' : '' }}">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">User</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item {{ Request::is('dashboard/user/create') ? 'active' : '' }}" href="{{ route('user.create') }}">Create</a></li>
            <li><a class="dropdown-item {{ Request::is('dashboard/user/report') ? 'active' : '' }}" href="{{ route('user.report') }}">Report</a></li>
          </ul>
        </li>

        {{-- Account --}}
        <li class="nav-item dropdown {{ Request::is('dashboard/account*') ? 'active' : '' }}">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Account</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item {{ Request::is('dashboard/account/profile') ? 'active' : '' }}" href="{{ route('account.profile') }}">Profile</a></li>
            <li><a class="dropdown-item {{ Request::is('dashboard/account/change-password') ? 'active' : '' }}" href="{{ route('account.change-password') }}">Change Password</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="dropdown-item text-danger" type="submit">Logout</button>
              </form>
            </li>
          </ul>
        </li>

      </ul>
    </div>
  </div>
</nav>
