<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduScan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
     <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f8fafc;font-family:"Segoe UI",sans-serif;}
        .sidebar{
            position:fixed;
            left:0;top:0;
            width:270px;
            height:100vh;
            background:#0f172a;
            color:#fff;
            display:flex;
            flex-direction:column;
            box-shadow:5px 0 25px rgba(0,0,0,.08);
        }
        .logo{
            padding:28px;
            text-align:center;
            font-size:26px;
            font-weight:bold;
            border-bottom:1px solid rgba(255,255,255,.08);
        }
        .logo span{color:#60a5fa;}
        .sidebar-menu{
            flex:1;
            overflow-y:auto;
            padding:20px 12px;
        }
        .sidebar-menu a{
            display:flex;
            align-items:center;
            gap:14px;
            color:#cbd5e1;
            text-decoration:none;
            padding:14px 18px;
            margin-bottom:8px;
            border-radius:12px;
            transition:.25s;
        }
        .sidebar-menu a i{font-size:19px;width:24px;}
        .sidebar-menu a:hover{background:#2563eb;color:#fff;transform:translateX(5px);}
        .sidebar-menu a.active{background:#2563eb;color:#fff;}
        .logout{
            margin-top:auto;
            padding:20px;
            border-top:1px solid rgba(255,255,255,.08);
            background:#0f172a;
        }
        .content{margin-left:270px;min-height:100vh;}
        .topbar{background:#fff;height:75px;display:flex;justify-content:space-between;align-items:center;padding:0 35px;box-shadow:0 2px 15px rgba(0,0,0,.05);}
        .user-box{display:flex;align-items:center;gap:12px;}
        .user-box i{font-size:30px;color:#2563eb;}
        .page{padding:30px;}
        .card{border:none;border-radius:18px;box-shadow:0 8px 20px rgba(0,0,0,.05);}
        footer{text-align:center;padding:20px;color:#94a3b8;}
        @media(max-width:992px){
            .sidebar{width:85px;}
            .sidebar span{display:none;}
            .content{margin-left:85px;}
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🎓 <span>EduScan</span></div>
    
    <div class="sidebar-menu">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>
        <a href="{{ route('obs.students') }}" class="{{ request()->routeIs('obs.*') && !request()->routeIs('obs.pending') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i> <span>OBS Öğrencileri</span>
        </a>
        <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'active' : '' }}">
            <i class="bi bi-person-vcard-fill"></i> <span>Öğrenci Yönetimi</span>
        </a>
        <a href="{{ route('teachers.index') }}" class="{{ request()->routeIs('teachers.*') ? 'active' : '' }}">
            <i class="bi bi-person-workspace"></i> <span>Öğretmenler</span>
        </a>
        <a href="{{ route('departments.index') }}" class="{{ request()->routeIs('departments.*') ? 'active' : '' }}">
            <i class="bi bi-building"></i> <span>Bölümler</span>
        </a>
        <a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*') ? 'active' : '' }}">
            <i class="bi bi-book-half"></i> <span>Dersler</span>
        </a>
        <a href="{{ route('exams.index') }}" class="{{ request()->routeIs('exams.*') ? 'active' : '' }}">
            <i class="bi bi-pencil-square"></i> <span>Sınavlar</span>
        </a>
        <a href="{{ route('results.index') }}" class="{{ request()->routeIs('results.*') ? 'active' : '' }}">
            <i class="bi bi-clipboard-data"></i> <span>Sonuçlar</span>
        </a>
        <a href="{{ route('obs.pending') }}" class="{{ request()->routeIs('obs.pending') ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle-fill"></i> <span>Onay Bekleyenler</span>
        </a>
    </div>

    <div class="logout">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="btn btn-danger w-100">
                <i class="bi bi-box-arrow-right"></i> <span>Çıkış Yap</span>
            </button>
        </form>
    </div>
</div>

<div class="content">
    <div class="topbar">
        <h4>@yield('title')</h4>
        <div class="user-box">
            <i class="bi bi-person-circle"></i>
            <div>
                <strong>Yönetici</strong><br>
                <small class="text-muted">{{ now()->format('d.m.Y') }}</small>
            </div>
        </div>
    </div>
    <div class="page">
        @yield('content')
    </div>
    <footer>
        © {{ date('Y') }} on EduScan - Tüm Hakları Saklıdır.
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>