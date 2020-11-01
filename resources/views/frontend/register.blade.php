<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>AloOrder | Dịch vụ đặt hàng Trung Quốc</title>

  <meta content="" name="descriptison">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">
  <link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Raleway:300,300i,400,400i,500,500i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/icofont/icofont.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/animate.css/animate.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/line-awesome/css/line-awesome.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/venobox/venobox.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/owl.carousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/aos/aos.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">

  <link rel="shortcut icon" href="{{ asset('images/favicon.svg') }}">

  <style>
    .option-hide {
        display: none;
    }
  </style>
</head>

<body>

  <!-- ======= Header ======= -->
  <header id="header" class="fixed-top d-flex align-items-center  header-transparent header-scrolled">
    <div class="container d-flex align-items-center">

      <div class="logo mr-auto">
        <h1 class="text-light"><a href="/">AloOrder</a></h1>
      </div>

      <nav class="nav-menu d-none d-lg-block">
        <ul>
          <li><a href="{{ route('admin.login') }}">Đăng nhập</a></li>
        </ul>
      </nav><!-- .nav-menu -->

    </div>
  </header><!-- End Header -->


  <main id="main">

    

    

    <!-- ======= Cta Section ======= -->
    <section id="cta" class="cta" style="padding: 40px;">
      <div class="container">


      </div>
    </section><!-- End Cta Section -->


    <!-- ======= Contact Section ======= -->
    <section id="register" class="contact">
      <div class="container">

        <div class="section-title" data-aos="zoom-out">
          <h2>Trở thành khách hàng</h2>
          <p>Đăng ký tài khoản</p>
        </div>

        <div class="row">

          <div class="col-lg-12" data-aos="fade-right">
            <form action="{{ route('customer.postRegister') }}" id="register-form" method="post">
              {{ csrf_field() }}
              @if (isset($errors))
                {{-- {{ dd($errors) }} --}}
              @endif
              
              <div class="form-group{{ $errors->has('symbol_name') ? ' has-error' : '' }}">
                  <label for="symbol_name" class="control-label">Mã khách hàng <span class="error">(*)</span></label>
                  <input id="symbol_name" type="text" class="form-control" 
                      name="symbol_name" value="{{ old('symbol_name') }}" 
                      placeholder="VD: thuyanh234">
                  
                  <i class="" style="font-size: 12px;">
                      <i class="fa fa-info-circle" aria-hidden="true"></i> 
                      Mã khách hàng là mã khách ghi trên kiện hàng để kho có thể phân loại được. Mã khách hàng để bên cạnh tên người nhận tiếng trung.
                  </i> <br>

                  @if ($errors->has('symbol_name'))
                      <span class="help-block">
                          <strong>{{ $errors->first('symbol_name') }}</strong>
                      </span>
                  @endif
              </div>

              <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                  <label for="email" class="control-label">E-Mail  <span class="error">(*)</span></label>
                  <input id="email" type="email" class="form-control" 
                      name="email" 
                      value="{{ old('email') }}" 
                      placeholder="VD: abc@gmail.com"
                      >

                  @if ($errors->has('email'))
                      <span class="help-block">
                          <strong>{{ $errors->first('email') }}</strong>
                      </span>
                  @endif
              </div>

              <div class="form-group{{ isset($errors) && $errors->has('province') ? ' has-error' : '' }}">
                <label for="address" class="control-label">Địa chỉ  <span class="error">(*)</span></label>

                    <select name="province" id="province" class="form-control" value={{ old('province')}}>
                        <option value="" checked>{{ trans('admin.province') }}</option>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->province_id }}">{{ $province->name }}</option>
                        @endforeach
                    </select>
                    @if (isset($errors) && $errors->has('province'))
                        <label id="province-error" class="error" for="province" >{{ $errors->first('province') }}</label>
                    @endif
            </div>

            <div class="form-group{{ isset($errors) && $errors->has('district') ? ' has-error' : '' }}">
                {{-- <div class="col-md-12"> --}}
                    <select name="district" id="district" class="form-control">
                        <option value="" checked>{{ trans('admin.district') }}</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->district_id }}" class="option-hide" data-parent-province={{$district->province_id}}
                                >{{ $district->name }}</option>
                        @endforeach
                    </select>
                    @if (isset($errors) && $errors->has('district'))
                        <label id="district-error" class="error" for="district">{{ $errors->first('district') }}</label>
                    @endif
                {{-- </div> --}}
            </div>

            <div class="form-group{{ $errors->has('address') ? ' has-error' : '' }}">
               
                {{-- <div class="col-md-12"> --}}
                    <input id="address" type="text" class="form-control" 
                        name="address" 
                        value="{{ old('address') }}" 
                        placeholder="Số nhà, tên đường"
                        >

                    @if ($errors->has('address'))
                        <span class="help-block">
                            <strong>{{ $errors->first('address') }}</strong>
                        </span>
                    @endif
                {{-- </div> --}}
            </div>

              <div class="form-group{{ $errors->has('mobile') ? ' has-error' : '' }}">
                  <label for="mobile" class="control-label">Số điện thoại  <span class="error">(*)</span></label>
                  <input id="mobile" type="text" class="form-control" 
                      name="mobile" 
                      value="{{ old('mobile') }}" 
                      placeholder="..."
                      >

                  @if ($errors->has('mobile'))
                      <span class="help-block">
                          <strong>{{ $errors->first('mobile') }}</strong>
                      </span>
                  @endif
              </div>

              <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                  <label for="password" class="control-label">Mật khẩu  <span class="error">(*)</span></label>
                  <input id="password" type="password" class="form-control" 
                      name="password" 
                      
                      placeholder="Mật khẩu">

                  @if ($errors->has('password'))
                      <span class="help-block">
                          <strong>{{ $errors->first('password') }}</strong>
                      </span>
                  @endif
              </div>

              <div class="form-group">
                  <label for="password-confirm" class="control-label">Mật khẩu xác nhận</label>
                  <input id="password-confirm" type="password" 
                      class="form-control" 
                      name="password_confirmation" 
                      placeholder="Mật khẩu xác nhận"
                      >
              </div>

              <div class="form-group">
                <button type="submit" class="btn btn-info">
                    Đăng ký
                </button>
              </div>
          </form>

          </div>

        </div>

      </div>
    </section><!-- End Contact Section -->

  </main><!-- End #main -->

  <!-- ======= Footer ======= -->
  <footer id="footer">
    <div class="container">
      <h3>AloOrder</h3>
      <p>Sự hài lòng của khách hàng là niềm tự hào của chúng tôi</p>
      <div class="social-links">
        <a href="#" class="twitter"><i class="bx bxl-twitter"></i></a>
        <a href="#" class="facebook"><i class="bx bxl-facebook"></i></a>
        <a href="#" class="instagram"><i class="bx bxl-instagram"></i></a>
        <a href="#" class="google-plus"><i class="bx bxl-skype"></i></a>
        <a href="#" class="linkedin"><i class="bx bxl-linkedin"></i></a>
      </div>
      <div class="copyright">
        &copy; Copyright <strong><span>AloOrder</span></strong>. All Rights Reserved
      </div>
      <div class="credits">
        Thiết kế và xây dựng <a href="https://www.facebook.com/th4nhtunq/">TungThanhDao</a>
      </div>
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top"><i class="ri-arrow-up-line"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/jquery.easing/jquery.easing.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/php-email-form/validate.js') }}"></script>
  <script src="{{ asset('assets/vendor/isotope-layout/isotope.pkgd.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/venobox/venobox.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/owl.carousel/owl.carousel.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/aos/aos.js') }}"></script>

  <!-- Template Main JS File -->
  <script src="{{ asset('assets/js/main.js') }}"></script>

<script>
    $(document).ready(function() {
        $('#province').on('change', function () {
            let province_id = $(this).val();
            $('#district option').removeClass("option-hide");
            $('#district option').addClass("option-hide");
            $('#district option[data-parent-province="'+province_id+'"]').removeClass("option-hide");
            console.log(province_id);
        });
    });
</script>

</body>

</html>