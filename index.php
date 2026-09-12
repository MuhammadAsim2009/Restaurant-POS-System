<?php
// ============================================================
// Ultra-Modern Login Page — Khan Fast Food POS
// ============================================================
require_once __DIR__ . '/config/session.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    $redirect = $_SESSION['user_role'] === 'manager' ? '/Resturent_POS/dashboard.php' : '/Resturent_POS/pos.php';
    header("Location: $redirect");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Resturant</title>
  <link rel="stylesheet" href="/Resturent_POS/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* Ultra Modern Split-Screen Login */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Outfit', 'Inter', sans-serif;
      background: #000;
      color: #fff;
      height: 100vh;
      overflow: hidden;
      display: flex;
    }
    
    /* Left Side - Visual */
    .login-visual {
      flex: 1.2;
      position: relative;
      background: url('https://images.unsplash.com/photo-1550547660-d9450f859349?q=80&w=2000&auto=format&fit=crop') center/cover no-repeat;
      display: none;
    }
    @media (min-width: 900px) {
      .login-visual { display: flex; flex-direction: column; justify-content: flex-end; padding: 60px; }
    }
    .visual-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.2) 100%);
    }
    .visual-content {
      position: relative; z-index: 2;
      max-width: 500px;
      animation: slideUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    .visual-content h1 {
      font-size: 48px; font-weight: 800; line-height: 1.1; margin-bottom: 16px;
      background: linear-gradient(135deg, #fff 0%, #d1d5db 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .visual-content p {
      font-size: 18px; color: #9ca3af; font-weight: 300; line-height: 1.5;
    }
    
    /* Right Side - Form */
    .login-panel {
      flex: 1;
      background: #0A0F1E;
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      padding: 40px; position: relative;
    }
    .login-container {
      width: 100%; max-width: 420px;
      animation: fadeIn 0.6s ease 0.2s both;
    }
    
    .logo-badge {
      width: 56px; height: 56px;
      background: rgba(245, 158, 11, 0.1);
      border: 1px solid rgba(245, 158, 11, 0.2);
      border-radius: 16px; display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin-bottom: 24px; color: #F59E0B;
    }
    
    .login-header h2 { font-size: 32px; font-weight: 700; margin-bottom: 8px; color: #fff; }
    .login-header p { font-size: 15px; color: #6b7280; margin-bottom: 32px; }
    
    /* Role Selector */
    .role-toggle {
      display: flex; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);
      border-radius: 12px; padding: 4px; margin-bottom: 32px; position: relative;
    }
    .role-btn {
      flex: 1; padding: 12px; background: transparent; border: none;
      color: #9ca3af; font-size: 14px; font-weight: 600; cursor: pointer;
      border-radius: 8px; transition: all 0.3s; z-index: 1;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .role-btn.active { color: #fff; }
    .role-slider {
      position: absolute; top: 4px; bottom: 4px; width: calc(50% - 4px);
      background: #1F2937; border-radius: 8px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      z-index: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05);
    }
    .role-toggle[data-active="cashier"] .role-slider { left: 4px; }
    .role-toggle[data-active="manager"] .role-slider { left: calc(50%); }

    /* Inputs */
    .input-wrapper { margin-bottom: 20px; position: relative; }
    .input-wrapper label {
      display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 8px;
    }
    .modern-input {
      width: 100%; padding: 14px 16px 14px 44px;
      background: #111827; border: 1px solid #374151;
      border-radius: 12px; color: #fff; font-size: 15px; font-family: inherit;
      transition: all 0.2s ease; outline: none;
    }
    .modern-input:focus {
      background: #1F2937; border-color: #F59E0B;
      box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
    }
    .modern-input::placeholder { color: #4B5563; }
    .input-icon {
      position: absolute; left: 16px; top: 39px; color: #6b7280; font-size: 16px;
      transition: color 0.2s ease;
    }
    .modern-input:focus + .input-icon { color: #F59E0B; }
    
    .eye-btn {
      position: absolute; right: 16px; top: 39px;
      background: none; border: none; color: #6b7280; cursor: pointer; font-size: 16px;
    }
    .eye-btn:hover { color: #fff; }

    /* Submit Button */
    .submit-btn {
      width: 100%; padding: 16px; margin-top: 12px;
      background: #F59E0B; color: #000; border: none; border-radius: 12px;
      font-size: 16px; font-weight: 700; font-family: inherit; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 10px;
      transition: all 0.2s ease; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.25);
    }
    .submit-btn:hover {
      background: #fbbf24; transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
    }
    .submit-btn:active { transform: translateY(0); }

    /* Demo Hint */
    .demo-hint {
      margin-top: 32px; padding: 16px;
      background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1);
      border-radius: 12px; text-align: center; font-size: 13px; color: #9ca3af;
    }
    .demo-hint strong { color: #F59E0B; }
    .demo-hint span { display: inline-block; background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; margin: 0 2px; color: #fff; }

    /* Animations */
    @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  </style>
</head>
<body>

  <div class="login-visual">
    <div class="visual-overlay"></div>
    <div class="visual-content">
      <h1>Elevate your dining experience.</h1>
      <p>The next-generation Point of Sale system designed specifically for speed, simplicity, and scale at Resturant Management System.</p>
    </div>
  </div>

  <div class="login-panel">
    <div class="login-container">
      <div class="logo-badge"><i class="fas fa-hamburger"></i></div>
      
      <div class="login-header">
        <h2>Welcome back</h2>
        <p>Please enter your details to sign in.</p>
      </div>

      <div class="role-toggle" id="roleToggle" data-active="cashier">
        <div class="role-slider"></div>
        <button class="role-btn active" id="btnCashier" onclick="setRole('cashier')"><i class="fas fa-desktop"></i> Cashier</button>
        <button class="role-btn" id="btnManager" onclick="setRole('manager')"><i class="fas fa-chart-line"></i> Manager</button>
      </div>

      <form id="loginForm">
        <div class="input-wrapper">
          <label>Username</label>
          <input type="text" id="username" class="modern-input" placeholder="Enter username" required value="cashier1">
          <i class="fas fa-user input-icon"></i>
        </div>

        <div class="input-wrapper">
          <label>Password</label>
          <input type="password" id="password" class="modern-input" placeholder="••••••••" required>
          <i class="fas fa-lock input-icon"></i>
          <button type="button" class="eye-btn" onclick="togglePwd()"><i class="fas fa-eye" id="eyeIcon"></i></button>
        </div>

        <div id="loginError" style="display:none; color:#ef4444; font-size:13px; margin-bottom:16px; padding:12px; background:rgba(239,68,68,0.1); border-radius:8px; border:1px solid rgba(239,68,68,0.2)"></div>

        <button type="submit" class="submit-btn" id="loginBtn">
          Sign In <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <!-- <div class="demo-hint">
        <strong>Demo Access</strong><br>
        Manager: <span>manager</span> / <span>password</span><br>
        Cashier: <span>cashier1</span> / <span>password</span>
      </div> -->
    </div>
  </div>

<div id="toastContainer" class="toast-container"></div>
<script src="/Resturent_POS/js/app.js"></script>
<script>
  function setRole(role) {
    const toggle = document.getElementById('roleToggle');
    toggle.setAttribute('data-active', role);
    document.getElementById('btnCashier').classList.toggle('active', role === 'cashier');
    document.getElementById('btnManager').classList.toggle('active', role === 'manager');
    
    const userInp = document.getElementById('username');
    userInp.value = role === 'manager' ? 'manager' : 'cashier1';
    document.getElementById('password').value = '';
    document.getElementById('password').focus();
  }

  function togglePwd() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') { pwd.type = 'text'; icon.className = 'fas fa-eye-slash'; }
    else { pwd.type = 'password'; icon.className = 'fas fa-eye'; }
  }

  document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn  = document.getElementById('loginBtn');
    const err  = document.getElementById('loginError');
    const user = document.getElementById('username').value.trim();
    const pass = document.getElementById('password').value;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
    err.style.display = 'none';

    try {
      const res = await api('auth.php?action=login', 'POST', { username: user, password: pass });
      if (res.success) {
        btn.innerHTML = '<i class="fas fa-check"></i> Welcome!';
        btn.style.background = '#10B981';
        btn.style.color = '#fff';
        showToast(`Welcome, ${res.user.name}!`, 'success');
        setTimeout(() => window.location.href = res.redirect, 800);
      }
    } catch (ex) {
      err.style.display = 'block';
      err.textContent = ex.message || 'Login failed. Please check your credentials.';
      btn.disabled = false;
      btn.innerHTML = 'Sign In <i class="fas fa-arrow-right"></i>';
    }
  });
</script>
</body>
</html>
