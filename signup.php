<?php
// signup.php — Student registration with full username validation
require_once 'includes/config.php';

if (!empty($_SESSION['student_id'])) {
    header('Location: dashboard.php'); exit;
}

$errors = [];

// Keep field values on validation error
$old = ['full_name'=>'','username'=>'','email'=>'','mobile'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    verify_csrf();

    $name     = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username']  ?? '');
    $email    = trim($_POST['email']     ?? '');
    $mobile   = trim($_POST['mobile']    ?? '');
    $pass     = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $old      = compact('name','username','email','mobile');

    // ── FULL NAME ─────────────────────────────────────────
    if (!$name) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($name) < 3) {
        $errors['full_name'] = 'Full name must be at least 3 characters.';
    }

    // ── USERNAME — 5 rules ────────────────────────────────
    if (!$username) {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($username) < 5) {
        // Rule 4: Minimum 5 characters
        $errors['username'] = 'Username must be at least 5 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        // Rule 3: Only letters and numbers (no special chars / spaces)
        $errors['username'] = 'Only letters (A-Z) and numbers (0-9) are allowed. No spaces or special characters.';
    } elseif (!preg_match('/[a-zA-Z]/', $username)) {
        // Rule 1: Must contain at least one letter
        $errors['username'] = 'Username must contain at least one letter (A-Z). Pure numbers are not allowed.';
    } elseif (!preg_match('/[0-9]/', $username)) {
        // Rule 2: Must contain at least one number
        $errors['username'] = 'Username must contain at least one number (0-9). Pure letters are not allowed.';
    } else {
        // Uniqueness check
        $chkU = $pdo->prepare("SELECT id FROM tblstudents WHERE username = ?");
        $chkU->execute([$username]);
        if ($chkU->rowCount() > 0) {
            $errors['username'] = 'This username is already taken. Please choose a different one.';
        }
    }

    // ── EMAIL ─────────────────────────────────────────────
    if (!$email) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } else {
        $chkE = $pdo->prepare("SELECT id FROM tblstudents WHERE email = ?");
        $chkE->execute([$email]);
        if ($chkE->rowCount() > 0) {
            $errors['email'] = 'This email is already registered. Please login.';
        }
    }

    // ── MOBILE (optional) ─────────────────────────────────
    if ($mobile && !preg_match('/^[0-9]{10,15}$/', $mobile)) {
        $errors['mobile'] = 'Mobile number must be 10-15 digits only.';
    }

    // ── PASSWORD ──────────────────────────────────────────
    if (!$pass) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($pass) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif ($pass !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // ── SAVE IF NO ERRORS ─────────────────────────────────
    if (empty($errors)) {
        $student_id = generate_student_id($pdo);
        $hashed     = md5($pass);
        try {
            $stmt = $pdo->prepare("INSERT INTO tblstudents
                (student_id,username,full_name,email,mobile,password,status)
                VALUES(?,?,?,?,?,?,1)");
            $stmt->execute([$student_id,$username,$name,$email,$mobile,$hashed]);
            set_flash('success',"Registration successful! Your Student ID is <strong>$student_id</strong>. Please login.");
            header('Location: index.php'); exit;
        } catch (\PDOException $e) {
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }
}

function fe(array $e, string $f): string {
    return isset($e[$f]) ? 'border-color:var(--danger);box-shadow:0 0 0 3px rgba(192,57,43,.15);' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Register — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .signup-wrap{min-height:100vh;background:linear-gradient(135deg,var(--navy) 0%,#1e3a5f 100%);display:flex;align-items:center;justify-content:center;padding:2rem}
    .signup-box{background:var(--white);border-radius:var(--radius-lg);padding:2.5rem;width:100%;max-width:540px;box-shadow:var(--shadow-lg)}
    .field-error{display:flex;align-items:center;gap:.35rem;color:var(--danger);font-size:.78rem;margin-top:.35rem;font-weight:500}
    .field-ok{display:flex;align-items:center;gap:.35rem;color:var(--success);font-size:.78rem;margin-top:.35rem;font-weight:500}
    .username-rules{margin-top:.6rem;padding:.8rem 1rem;background:#f8f9fa;border-radius:8px;border:1.5px solid var(--border);font-size:.79rem}
    .rule{display:flex;align-items:center;gap:.5rem;padding:.22rem 0;color:var(--text-muted);transition:color .2s}
    .rule.pass{color:var(--success)}
    .rule.fail{color:var(--danger)}
    .rule i{width:14px;text-align:center;font-size:.72rem}
    .strength-bar-wrap{height:4px;background:var(--border);border-radius:4px;overflow:hidden;margin-top:.4rem}
    .strength-bar{height:100%;width:0;border-radius:4px;transition:width .3s,background .3s}
    .input-icon-wrap{position:relative}
    .input-icon-wrap input{padding-right:2.5rem}
    .input-icon-wrap .tog{position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:.85rem}
    @media(max-width:480px){.signup-box{padding:1.75rem 1.25rem}.pw-grid{grid-template-columns:1fr!important}}
  </style>
</head>
<body>
<div class="signup-wrap">
<div class="signup-box">

  <div style="text-align:center;margin-bottom:1.75rem">
    <a href="index.php" style="font-family:var(--font-head);font-size:1.75rem;color:var(--navy);text-decoration:none">
      <i class="fa-solid fa-book-open-reader" style="color:var(--gold)"></i> <?= APP_NAME ?>
    </a>
    <p style="color:var(--text-muted);margin-top:.3rem;font-size:.88rem">Create your library account</p>
  </div>

  <?php if (!empty($errors['general'])): ?>
  <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= clean($errors['general']) ?></div>
  <?php endif; ?>

  <?php if (count($errors) > 0 && !isset($errors['general'])): ?>
  <div class="alert alert-danger" style="align-items:flex-start">
    <i class="fa fa-triangle-exclamation" style="margin-top:.1rem;flex-shrink:0"></i>
    <div>
      <strong>Please fix the following:</strong>
      <ul style="margin:.4rem 0 0 1.1rem;padding:0">
        <?php foreach($errors as $k=>$msg): if($k==='general') continue; ?>
        <li style="font-size:.84rem;margin-top:.2rem"><?= clean($msg) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST" id="signupForm" novalidate>
    <?= csrf_field() ?>

    <!-- Full Name -->
    <div class="form-group">
      <label class="form-label">Full Name <span style="color:var(--danger)">*</span></label>
      <input type="text" name="full_name" class="form-control"
             placeholder="Enter your full name"
             value="<?= clean($old['full_name']??'') ?>"
             style="<?= fe($errors,'full_name') ?>" required>
      <?php if(isset($errors['full_name'])): ?>
      <div class="field-error"><i class="fa fa-circle-exclamation"></i> <?= clean($errors['full_name']) ?></div>
      <?php endif; ?>
    </div>

    <!-- USERNAME -->
    <div class="form-group">
      <label class="form-label">
        Username <span style="color:var(--danger)">*</span>
        <span style="font-size:.72rem;font-weight:400;color:var(--text-muted);margin-left:.35rem">must have letters + numbers</span>
      </label>
      <div class="input-icon-wrap">
        <input type="text" name="username" id="username" class="form-control"
               placeholder="e.g. john99 or raj2024"
               value="<?= clean($old['username']??'') ?>"
               style="<?= fe($errors,'username') ?>"
               maxlength="30" autocomplete="username" required
               oninput="validateUsername(this.value)">
        <span id="uIcon" style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);font-size:.9rem"></span>
      </div>

      <!-- Server error -->
      <?php if(isset($errors['username'])): ?>
      <div class="field-error" id="uServerErr">
        <i class="fa fa-circle-exclamation"></i> <?= clean($errors['username']) ?>
      </div>
      <?php endif; ?>

      <!-- Live client error -->
      <div id="uClientErr" style="display:none"></div>

      <!-- Rules checklist -->
      <div class="username-rules" id="uRules">
        <div class="rule" id="r-len"><i class="fa fa-circle" id="i-len"></i> Minimum 5 characters</div>
        <div class="rule" id="r-let"><i class="fa fa-circle" id="i-let"></i> At least one letter (A-Z or a-z)</div>
        <div class="rule" id="r-num"><i class="fa fa-circle" id="i-num"></i> At least one number (0-9)</div>
        <div class="rule" id="r-chr"><i class="fa fa-circle" id="i-chr"></i> Only letters and numbers allowed (no spaces or symbols)</div>
      </div>
    </div>

    <!-- Email -->
    <div class="form-group">
      <label class="form-label">Email Address <span style="color:var(--danger)">*</span></label>
      <input type="email" name="email" id="email" class="form-control"
             placeholder="your@email.com"
             value="<?= clean($old['email']??'') ?>"
             style="<?= fe($errors,'email') ?>" required
             onblur="checkEmail(this.value)">
      <div id="emailMsg"></div>
      <?php if(isset($errors['email'])): ?>
      <div class="field-error"><i class="fa fa-circle-exclamation"></i> <?= clean($errors['email']) ?></div>
      <?php endif; ?>
    </div>

    <!-- Mobile -->
    <div class="form-group">
      <label class="form-label">Mobile Number <span style="font-size:.75rem;color:var(--text-muted)">(optional)</span></label>
      <input type="tel" name="mobile" class="form-control"
             placeholder="10-15 digit mobile number"
             value="<?= clean($old['mobile']??'') ?>"
             style="<?= fe($errors,'mobile') ?>"
             maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">
      <?php if(isset($errors['mobile'])): ?>
      <div class="field-error"><i class="fa fa-circle-exclamation"></i> <?= clean($errors['mobile']) ?></div>
      <?php endif; ?>
    </div>

    <!-- Password -->
    <div class="pw-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group">
        <label class="form-label">Password <span style="color:var(--danger)">*</span></label>
        <div class="input-icon-wrap">
          <input type="password" name="password" id="pwd" class="form-control"
                 placeholder="Min 8 characters"
                 style="<?= fe($errors,'password') ?>" required
                 oninput="pwStrength(this.value)">
          <button type="button" class="tog" onclick="togglePwd('pwd','pe')"><i class="fa fa-eye" id="pe"></i></button>
        </div>
        <div class="strength-bar-wrap"><div class="strength-bar" id="sBar"></div></div>
        <div id="sLabel" style="font-size:.72rem;font-weight:600;margin-top:.15rem;color:var(--text-muted)"></div>
        <?php if(isset($errors['password'])): ?>
        <div class="field-error"><i class="fa fa-circle-exclamation"></i> <?= clean($errors['password']) ?></div>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password <span style="color:var(--danger)">*</span></label>
        <div class="input-icon-wrap">
          <input type="password" name="confirm_password" id="cpwd" class="form-control"
                 placeholder="Repeat password"
                 style="<?= fe($errors,'confirm_password') ?>" required
                 oninput="checkMatch()">
          <button type="button" class="tog" onclick="togglePwd('cpwd','ce')"><i class="fa fa-eye" id="ce"></i></button>
        </div>
        <div id="matchMsg"></div>
        <?php if(isset($errors['confirm_password'])): ?>
        <div class="field-error"><i class="fa fa-circle-exclamation"></i> <?= clean($errors['confirm_password']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <button type="submit" name="signup" class="btn btn-primary"
            style="width:100%;justify-content:center;padding:.8rem;margin-top:.5rem;font-size:1rem">
      <i class="fa fa-user-plus"></i> Create Account
    </button>
  </form>

  <p style="text-align:center;margin-top:1.25rem;font-size:.88rem;color:var(--text-muted)">
    Already have an account? <a href="index.php" style="color:var(--navy);font-weight:600">Login here</a>
  </p>
</div>
</div>

<script>
// ── Username live validation ──────────────────────────────
function validateUsername(val) {
  var field = document.getElementById('username');
  var uIcon = document.getElementById('uIcon');
  var uCErr = document.getElementById('uClientErr');
  var uSErr = document.getElementById('uServerErr');
  if (uSErr) uSErr.style.display = 'none';

  var hasInput = val.length > 0;
  var okLen    = val.length >= 5;                   // Rule 4
  var okLetter = /[a-zA-Z]/.test(val);             // Rule 1
  var okNumber = /[0-9]/.test(val);                // Rule 2
  var okChars  = /^[a-zA-Z0-9]*$/.test(val);      // Rule 3

  // Update rule rows
  setRule('r-len','i-len', okLen,    hasInput);
  setRule('r-let','i-let', okLetter, hasInput);
  setRule('r-num','i-num', okNumber, hasInput);
  setRule('r-chr','i-chr', okChars,  hasInput);

  // Determine error message
  var msg = '';
  if (hasInput) {
    if (!okChars)
      msg = 'No spaces or special characters allowed — only letters and numbers.';
    else if (!okLen)
      msg = 'Too short! Minimum 5 characters required.';
    else if (!okLetter && !okNumber)
      msg = 'Must contain both letters (A-Z) and numbers (0-9).';
    else if (!okLetter)
      msg = 'Must include at least one letter (A-Z). Numbers-only username is not allowed.';
    else if (!okNumber)
      msg = 'Must include at least one number (0-9). Letters-only username is not allowed.';
  }

  if (msg) {
    uCErr.innerHTML = '<div class="field-error"><i class="fa fa-circle-exclamation"></i> ' + msg + '</div>';
    uCErr.style.display = 'block';
  } else {
    uCErr.innerHTML = '';
    uCErr.style.display = 'none';
  }

  var allOk = hasInput && okLen && okLetter && okNumber && okChars;
  field.style.borderColor = !hasInput ? '' : (allOk ? 'var(--success)' : 'var(--danger)');
  field.style.boxShadow   = !hasInput ? '' : (allOk ? '0 0 0 3px rgba(46,125,84,.15)' : '0 0 0 3px rgba(192,57,43,.12)');
  uIcon.innerHTML = !hasInput ? '' : (allOk
    ? '<i class="fa fa-circle-check" style="color:var(--success)"></i>'
    : '<i class="fa fa-circle-exclamation" style="color:var(--danger)"></i>');
}

function setRule(rid, iid, passed, hasInput) {
  var r = document.getElementById(rid);
  var i = document.getElementById(iid);
  r.className = 'rule' + (!hasInput ? '' : (passed ? ' pass' : ' fail'));
  i.className = 'fa ' + (!hasInput ? 'fa-circle' : (passed ? 'fa-circle-check' : 'fa-circle-xmark'));
}

// ── Email availability check ──────────────────────────────
function checkEmail(email) {
  var el = document.getElementById('emailMsg');
  var fd = document.getElementById('email');
  if (!email || !email.includes('@')) { el.innerHTML=''; return; }
  el.innerHTML = '<div class="field-ok"><i class="fa fa-spinner fa-spin"></i> Checking availability...</div>';
  fetch('check_availability.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'email='+encodeURIComponent(email)
  }).then(r=>r.json()).then(data=>{
    if (data.available) {
      el.innerHTML='<div class="field-ok"><i class="fa fa-circle-check"></i> Email is available</div>';
      fd.style.borderColor='var(--success)';
    } else {
      el.innerHTML='<div class="field-error"><i class="fa fa-circle-exclamation"></i> Email already registered</div>';
      fd.style.borderColor='var(--danger)';
    }
  }).catch(()=>{ el.innerHTML=''; });
}

// ── Password strength ─────────────────────────────────────
function pwStrength(v) {
  var bar=document.getElementById('sBar'), lbl=document.getElementById('sLabel');
  var s=0;
  if(v.length>=8)s++; if(v.length>=12)s++;
  if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^a-zA-Z0-9]/.test(v))s++;
  var L=[
    {w:'0%',  c:'',                 t:'',           tc:''},
    {w:'20%', c:'var(--danger)',    t:'Very Weak',  tc:'var(--danger)'},
    {w:'40%', c:'var(--warning)',   t:'Weak',       tc:'var(--warning)'},
    {w:'60%', c:'#f0b429',         t:'Fair',       tc:'#9a7200'},
    {w:'80%', c:'#4caf50',         t:'Strong',     tc:'#2e7d54'},
    {w:'100%',c:'var(--success)',   t:'Very Strong',tc:'var(--success)'}
  ];
  var l=v.length===0?L[0]:L[Math.min(s,5)];
  bar.style.width=l.w; bar.style.background=l.c;
  lbl.textContent=l.t; lbl.style.color=l.tc;
}

// ── Confirm password match ────────────────────────────────
function checkMatch() {
  var p=document.getElementById('pwd').value;
  var c=document.getElementById('cpwd');
  var m=document.getElementById('matchMsg');
  if(!c.value){m.innerHTML='';c.style.borderColor='';return;}
  if(c.value===p){
    m.innerHTML='<div class="field-ok"><i class="fa fa-circle-check"></i> Passwords match</div>';
    c.style.borderColor='var(--success)';
  } else {
    m.innerHTML='<div class="field-error"><i class="fa fa-circle-exclamation"></i> Passwords do not match</div>';
    c.style.borderColor='var(--danger)';
  }
}

// ── Toggle password visibility ────────────────────────────
function togglePwd(id, eid) {
  var f=document.getElementById(id), e=document.getElementById(eid);
  f.type=f.type==='password'?'text':'password';
  e.className=f.type==='password'?'fa fa-eye':'fa fa-eye-slash';
}

// ── Final validation on submit ────────────────────────────
document.getElementById('signupForm').addEventListener('submit', function(e) {
  var u   = document.getElementById('username').value.trim();
  var pwd = document.getElementById('pwd').value;
  var cpwd= document.getElementById('cpwd').value;
  var blocked = false;

  // USERNAME — all 5 rules
  if (!u) {
    markBad('username','Username is required.'); blocked=true;
  } else if (!/^[a-zA-Z0-9]+$/.test(u)) {
    markBad('username','Only letters and numbers allowed — no spaces or symbols.'); blocked=true;
  } else if (u.length < 5) {
    markBad('username','Username must be at least 5 characters.'); blocked=true;
  } else if (!/[a-zA-Z]/.test(u)) {
    markBad('username','Username must include at least one letter (A-Z).'); blocked=true;
  } else if (!/[0-9]/.test(u)) {
    markBad('username','Username must include at least one number (0-9).'); blocked=true;
  }

  // PASSWORD
  if (pwd.length < 8) {
    markBad('pwd','Password must be at least 8 characters.'); blocked=true;
  }
  if (pwd !== cpwd) {
    markBad('cpwd','Passwords do not match.'); blocked=true;
  }

  if (blocked) {
    e.preventDefault();
    window.scrollTo({top:0,behavior:'smooth'});
  }
});

function markBad(id, msg) {
  var el = document.getElementById(id);
  if (!el) return;
  el.style.borderColor = 'var(--danger)';
  el.style.boxShadow   = '0 0 0 3px rgba(192,57,43,.15)';
  // Append error below only if not already present
  var wrap = el.closest('.form-group') || el.parentElement;
  if (!wrap.querySelector('.js-ferr')) {
    var d = document.createElement('div');
    d.className = 'field-error js-ferr';
    d.innerHTML = '<i class="fa fa-circle-exclamation"></i> ' + msg;
    el.parentElement.after ? el.parentElement.insertAdjacentElement('afterend', d) : wrap.appendChild(d);
  }
}
</script>
</body>
</html>
