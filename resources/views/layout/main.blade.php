{{--
    Main layout (master template): every page @extends this file.
    @yield('content') is where each view injects its HTML (login, jobs, dashboard, etc.).
    Favicon + logo URLs come from config('app.brand_logo') and config('app.name').
--}}
<!doctype html>
<html class="no-js" lang="zxx">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
         <title>Job board </title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
		<link rel="icon" type="image/png" href="{{ asset(config('app.brand_logo')) }}">
		<link rel="apple-touch-icon" href="{{ asset(config('app.brand_logo')) }}">

		{{-- Theme CSS: Bootstrap base + job-board template + our overrides (custom.css) --}}
            <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/flaticon.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/price_rangs.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/slicknav.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/animate.min.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/magnific-popup.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/fontawesome-all.min.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/themify-icons.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/slick.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/nice-select.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/style.css')}}">
            <link rel="stylesheet" href="{{ asset('assets/css/custom.css')}}">
   </head>

   <body>
    {{-- Splash spinner while assets load (controlled by theme JS) --}}
    <div id="preloader-active">
        <div class="preloader d-flex align-items-center justify-content-center">
            <div class="preloader-inner position-relative">
                <div class="preloader-circle"></div>
                <div class="preloader-img pere-text">
                    <img src="{{ asset(config('app.brand_logo')) }}" alt="{{ config('app.name', 'Job Board') }}" class="app-logo app-logo--splash" width="420" height="200" decoding="async">
                </div>
            </div>
        </div>
    </div>
    <header>
       <div class="header-area header-transparrent">
           <div class="headder-top header-sticky">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-3 col-md-2">
                            <div class="logo">
                                <a href="{{ route('home') }}" class="app-logo-wrap" title="{{ config('app.name', 'Job Board') }}">
                                    <img src="{{ asset(config('app.brand_logo')) }}" alt="{{ config('app.name', 'Job Board') }}" class="app-logo app-logo--header" width="390" height="92" decoding="async">
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-9 col-md-9">
                            <div class="menu-wrapper">
                                {{-- Top links change by role: admin / company / applicant / guest --}}
                                <div class="main-menu">
                                    <nav class="d-none d-lg-block">
                                        <ul id="navigation">
                                            <li><a href="{{ route('home') }}">Home</a></li>
                                            @auth
                                                @if(auth()->user()->isAdmin())
                                                    <li><a href="{{ route('admin.dashboard') }}">Admin Panel</a></li>
                                                    <li><a href="{{ route('admin.companies.index') }}">Companies</a></li>
                                                    <li><a href="{{ route('admin.jobs.index') }}">All Jobs</a></li>
                                                    <li><a href="{{ route('admin.contacts.index') }}">Contact</a></li>
                                                    <li><a href="{{ route('admin.feedback.index') }}">Feedback</a></li>
                                                @elseif(auth()->user()->isCompany())
                                                    <li><a href="{{ route('company.jobs.index') }}">Company Panel</a></li>
                                                    <li><a href="{{ route('company.jobs.create') }}">Post Job</a></li>
                                                @else
                                                    <li><a href="{{ route('jobs.index') }}">Find a Jobs </a></li>
                                                    <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                                @endif
                                            @else
                                                <li><a href="{{ route('jobs.index') }}">Find a Jobs </a></li>
                                            @endauth
                                            <li><a href="{{ route('about') }}">About</a></li>
                                            {{-- Admins already have Contact + Feedback above (admin inbox). Avoid duplicate links. --}}
                                            @unless(auth()->check() && auth()->user()->isAdmin())
                                                <li><a href="{{ route('contact') }}">Contact</a></li>
                                                <li><a href="{{ route('feedback') }}">Feedback</a></li>
                                            @endunless
                                        </ul>
                                    </nav>
                                </div>          
                                {{-- Right side: Logout if logged in; otherwise Register + Login --}}
                                <div class="header-btn d-none d-lg-flex align-items-center jb-header-actions">
                                    @auth
                                        @if(auth()->user()->isCompany())
                                            {{-- Company: main links are in the menu above --}}
                                        @else
                                            {{-- Applicant: job link is in the menu --}}
                                        @endif

                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm head-btn2 jb-auth-btn jb-auth-btn--invert">Logout</button>
                                        </form>
                                    @else
                                        <a href="{{ route('register') }}" class="btn btn-sm head-btn2 jb-auth-btn jb-auth-btn--invert">Register</a>
                                        <a href="{{ route('login') }}" class="btn btn-sm head-btn2">Login</a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                        {{-- Small screens: theme builds menu from #navigation --}}
                        <div class="col-12">
                            <div class="mobile_menu d-block d-lg-none"></div>
                        </div>
                    </div>
                </div>
           </div>
       </div>
 
       {{-- Page-specific Blade views render here --}}
       <div class="content">
        @yield('content')
       </div>

    {{-- Shared footer on all pages using this layout --}}
    <footer class="pro-footer">
        <div class="container">
            <div class="row justify-content-between">
                <div class="col-lg-4 col-md-6 mb-4">
                    <a href="{{ route('home') }}" class="app-logo-wrap d-inline-block mb-3" title="{{ config('app.name', 'Job Board') }}">
                        <img src="{{ asset(config('app.brand_logo')) }}" alt="{{ config('app.name', 'Job Board') }}" class="app-logo app-logo--footer" width="370" height="98" decoding="async">
                    </a>
                    <p class="pro-footer-text mb-0">
                        A modern platform connecting top companies with talented applicants.
                    </p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="pro-footer-title">Quick Links</h5>
                    <ul class="pro-footer-links">
                        <li><a href="{{ route('home') }}">Home</a></li>
                        <li><a href="{{ route('about') }}">About</a></li>
                        <li><a href="{{ route('jobs.index') }}">Find Jobs</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                        <li><a href="{{ route('feedback') }}">Feedback</a></li>
                        @auth
                            <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        @else
                            <li><a href="{{ route('login') }}">Login</a></li>
                        @endauth
                    </ul>
                </div>
                <div class="col-lg-4 col-md-12 mb-4">
                    <h5 class="pro-footer-title">Contact</h5>
                    <ul class="pro-footer-contact">
                        <li><span>Email:</span> support@jobboard.com</li>
                        <li><span>Phone:</span> +92 987 654 321</li>
                        <li><span>Location:</span> Lahore, Pakistan</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

        {{-- Theme JavaScript: jQuery + Bootstrap + sliders; main.js ties behaviors together --}}
        <script src="{{ asset('assets/js/vendor/modernizr-3.5.0.min.js') }}"></script>
        <script src="{{ asset('assets/js/vendor/jquery-1.12.4.min.js') }}"></script>
        <script src="{{ asset('assets/js/popper.min.js') }}"></script>
        <script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.slicknav.min.js') }}"></script>
        <script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
        <script src="{{ asset('assets/js/slick.min.js') }}"></script>
        <script src="{{ asset('assets/js/price_rangs.js') }}"></script>
        <script src="{{ asset('assets/js/wow.min.js') }}"></script>
        <script src="{{ asset('assets/js/animated.headline.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.magnific-popup.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.scrollUp.min.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.nice-select.min.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.sticky.js') }}"></script>
        <script src="{{ asset('assets/js/contact.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.form.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('assets/js/mail-script.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.ajaxchimp.min.js') }}"></script>
        <script src="{{ asset('assets/js/plugins.js') }}"></script>
        <script src="{{ asset('assets/js/main.js') }}"></script>
        
    </body>
</html>