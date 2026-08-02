<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <!-- Mobil ölçeklemeyi sabitleyen güncellenmiş viewport etiketi -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <title>EduScan</title>

    <!-- Güvenlik Token'ı (Eklendi) -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            background:#f8fafc;
            font-family:"Segoe UI",sans-serif;
        }

        .sidebar{
            position:fixed;
            left:0;
            top:0;
            width:270px;
            height:100vh;
            background:#0f172a;
            color:#fff;
            display:flex;
            flex-direction:column;
            box-shadow:5px 0 25px rgba(0,0,0,.08);
            z-index: 1050; /* Menünün mobilde üstte kalması için eklendi */
            transition: all 0.3s ease-in-out; /* Kayma animasyonu için eklendi */
        }

        .logo{
            padding:28px;
            text-align:center;
            font-size:26px;
            font-weight:bold;
            border-bottom:1px solid rgba(255,255,255,.08);
        }

        .logo span{
            color:#60a5fa;
        }

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

        .sidebar-menu a i{
            font-size:19px;
            width:24px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active{
            background:#2563eb;
            color:#fff;
        }

        .logout{
            margin-top:auto;
            padding:20px;
            border-top:1px solid rgba(255,255,255,.08);
            background:#0f172a;
        }

        .content{
            margin-left:270px;
            min-height:100vh;
            transition: all 0.3s ease-in-out;
        }

        .topbar{
            background:#fff;
            height:75px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:0 35px;
            box-shadow:0 2px 15px rgba(0,0,0,.05);
        }

        .user-box{
            display:flex;
            align-items:center;
            gap:12px;
        }

        .user-box i{
            font-size:30px;
            color:#2563eb;
        }

        .page{
            padding:30px;
        }

        .card{
            border:none;
            border-radius:18px;
            box-shadow:0 8px 20px rgba(0,0,0,.05);
        }

        footer{
            text-align:center;
            padding:20px;
            color:#94a3b8;
        }

        /* --- MOBİL UYUMLULUK KODLARI --- */
        #hamburger-btn, #sidebar-overlay {
            display: none; /* Masaüstünde gizle */
        }

        /* Tablet Görünümü (Sidebar Daralır) */
        @media(max-width:992px){
            .sidebar{
                width:85px;
            }
            .sidebar span{
                display:none;
            }
            .content{
                margin-left:85px;
            }
        }

        /* Mobil Görünüm (Sidebar Gizlenir, Hamburger Menü Çıkar) */
        @media (max-width: 768px) {
            #hamburger-btn {
                display: inline-block;
                background: none;
                border: none;
                font-size: 28px;
                cursor: pointer;
                padding: 0 15px 0 0;
                color: #333;
            }

            .sidebar {
                width: 270px; /* Menü açıldığında yazılar okunsun diye genişliği sıfırla */
                left: -270px; /* Menüyü ekranın sol dışına sakla */
            }

            .sidebar span {
                display: inline; /* Tablette gizlenen yazıları mobilde menü açılınca göster */
            }

            .sidebar.active {
                left: 0; /* Butona basılınca menüyü ekrana kaydır */
            }

            .content {
                margin-left: 0; /* İçerik mobilde tam ekran olsun */
            }

            .topbar {
                padding: 0 15px; /* Mobilde topbar boşluklarını daralt */
            }

            .page {
                padding: 15px; /* Mobilde içerik boşluklarını daralt */
            }

            #sidebar-overlay.active {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.5);
                z-index: 1040; /* Menünün bir altı, içeriğin üstü */
            }
        }
    </style>
</head>
<body>

<!-- Mobilde arka planı karartacak div -->
<div id="sidebar-overlay"></div>

<div class="sidebar">

    <div class="logo">
        🎓 <span>EduScan</span>
    </div>

    <div class="sidebar-menu">

        <!-- Dashboard -->
        <a href="{{ route('obs.dashboard') }}"
           class="{{ request()->routeIs('obs.dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <!-- Optik Tarama -->
        <a href="{{ route('obs.tarama') }}"
           class="{{ request()->routeIs('obs.tarama') ? 'active' : '' }}">
            <i class="bi bi-people-fill"></i>
            <span>OBS Öğrencileri</span>
        </a>

        <!-- Öğrenci Yönetimi -->
        <a href="{{ route('obs.students.index') }}"
           class="{{ request()->routeIs('obs.students.*') ? 'active' : '' }}">
            <i class="bi bi-person-vcard-fill"></i>
            <span>Öğrenci Yönetimi</span>
        </a>

        <!-- Öğretmenler -->
        <a href="{{ route('obs.teachers.index') }}"
           class="{{ request()->routeIs('obs.teachers.*') ? 'active' : '' }}">
            <i class="bi bi-person-workspace"></i>
            <span>Öğretmenler</span>
        </a>

        <!-- Bölümler -->
        <a href="{{ route('obs.departments.index') }}"
           class="{{ request()->routeIs('obs.departments.*') ? 'active' : '' }}">
            <i class="bi bi-building"></i>
            <span>Bölümler</span>
        </a>

        <!-- Dersler -->
        <a href="{{ route('obs.courses.index') }}"
           class="{{ request()->routeIs('obs.courses.*') ? 'active' : '' }}">
            <i class="bi bi-book-half"></i>
            <span>Dersler</span>
        </a>

        <!-- Sınavlar -->
        <a href="{{ route('obs.exams.index') }}"
           class="{{ request()->routeIs('obs.exams.*') ? 'active' : '' }}">
            <i class="bi bi-pencil-square"></i>
            <span>Sınavlar</span>
        </a>

        <!-- Sonuçlar -->
        <a href="{{ route('obs.results.index') }}"
           class="{{ request()->routeIs('obs.results.*') ? 'active' : '' }}">
            <i class="bi bi-clipboard-data"></i>
            <span>Sonuçlar</span>
        </a>

        <!-- Bekleyenler -->
        <a href="{{ route('obs.pending') }}"
           class="{{ request()->routeIs('obs.pending') ? 'active' : '' }}">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Onay Bekleyenler</span>
        </a>

    </div>

    <div class="logout">
        <form action="{{ route('obs.logout') }}" method="POST">
            @csrf
            <button class="btn btn-danger w-100">
                <i class="bi bi-box-arrow-right"></i>
                <span>Çıkış Yap</span>
            </button>
        </form>
    </div>

</div>

<div class="content">

    <div class="topbar">
        <!-- Başlık ve Hamburger Butonu Yan Yana -->
        <div class="d-flex align-items-center">
            <button id="hamburger-btn">
                <i class="bi bi-list"></i>
            </button>
            <h4 class="m-0">
                @yield('title')
            </h4>
        </div>

        <div class="user-box">
            <i class="bi bi-person-circle"></i>
            <div>
                <strong>Yönetici</strong>
                <br>
                <small class="text-muted">
                    {{ now()->format('d.m.Y') }}
                </small>
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

<!-- Hamburger Menü İçin JavaScript -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebar = document.querySelector('.sidebar');

        // Hamburger ikonuna tıklanınca menüyü aç
        if(hamburgerBtn) {
            hamburgerBtn.addEventListener('click', function() {
                sidebar.classList.add('active');
                sidebarOverlay.classList.add('active');
            });
        }

        // Karartılmış boş alana tıklanınca menüyü kapat
        if(sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sayfaya özel JS kodlarının geleceği yer (Eklendi) -->
@yield('scripts')

</body>
</html>