<?php
session_start();
$loggedInUser = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login | BMI Calculator</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <style>
      body {
        background: linear-gradient(135deg, #eef7ff, #f8f9fa);
        min-height: 100vh;
      }
      .auth-card {
        max-width: 460px;
        border: 0;
        border-radius: 1rem;
      }
    </style>
  </head>
  <body>
    <main class="container py-5">
      <div class="card auth-card shadow-lg mx-auto">
        <div class="card-body p-4 p-md-5">
          <div class="text-center mb-4">
            <a href="index.html" class="text-decoration-none fw-bold text-primary">BMI Calculator</a>
            <h1 class="h2 fw-bold mt-3">Welcome back</h1>
            <p class="text-muted mb-0">Sign in to save and view your BMI history.</p>
          </div>

          <?php if ($loggedInUser): ?>
            <div class="alert alert-success" role="alert">
              You are already logged in as <?= htmlspecialchars($loggedInUser, ENT_QUOTES, 'UTF-8') ?>.
            </div>
            <div class="d-grid gap-2">
              <a class="btn btn-primary" href="index.html">Go to calculator</a>
              <button id="logoutButton" class="btn btn-outline-secondary" type="button">Logout</button>
            </div>
          <?php else: ?>
            <div id="message" class="alert d-none" role="alert"></div>
            <form id="loginForm" novalidate>
              <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input id="email" name="email" type="email" class="form-control form-control-lg" autocomplete="email" required />
              </div>
              <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input id="password" name="password" type="password" class="form-control form-control-lg" minlength="6" autocomplete="current-password" required />
              </div>
              <div class="d-grid">
                <button id="submitButton" class="btn btn-primary btn-lg" type="submit">Login</button>
              </div>
            </form>
            <p class="text-center text-muted small mt-4 mb-0">
              New here? Use the same form to
              <button id="registerButton" class="btn btn-link btn-sm p-0 align-baseline" type="button">create an account</button>.
            </p>
          <?php endif; ?>
        </div>
      </div>
    </main>

    <script>
      const form = document.getElementById("loginForm");
      const registerButton = document.getElementById("registerButton");
      const logoutButton = document.getElementById("logoutButton");
      let action = "login";

      function showMessage(text, type) {
        const message = document.getElementById("message");
        message.textContent = text;
        message.className = `alert alert-${type}`;
      }

      function submitAuth(event) {
        event.preventDefault();
        const submitButton = document.getElementById("submitButton");
        submitButton.disabled = true;
        fetch("backend.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            action,
            email: document.getElementById("email").value,
            password: document.getElementById("password").value
          })
        })
          .then((response) => response.json())
          .then((data) => {
            if (!data.success) throw new Error(data.message);
            window.location.href = "index.html";
          })
          .catch((error) => showMessage(error.message || "Authentication failed.", "danger"))
          .finally(() => { submitButton.disabled = false; });
      }

      if (form) form.addEventListener("submit", submitAuth);
      if (registerButton) {
        registerButton.addEventListener("click", () => {
          action = "register";
          document.getElementById("submitButton").textContent = "Create account";
          registerButton.classList.add("d-none");
          showMessage("Enter your email and a password of at least 6 characters.", "info");
        });
      }
      if (logoutButton) {
        logoutButton.addEventListener("click", () => {
          fetch("backend.php", { method: "POST", body: new URLSearchParams({ action: "logout" }) })
            .then(() => window.location.reload());
        });
      }
    </script>
  </body>
</html>
