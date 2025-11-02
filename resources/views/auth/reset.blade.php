<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="{{ asset('css/reset-password.css') }}">
</head>
<style>
/* Reset CSS */
body, h1, form, label, input, button {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

/* Container */
.container {
    width: 100%;
    max-width: 480px;
    padding: 20px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

/* Form Wrapper */
.form-wrapper {
    text-align: center;
}

/* Form Group */
.form-group {
    margin-bottom: 15px;
    text-align: left;
}

/* Labels */
label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

/* Inputs */
input[type="password"],
input[type="text"] {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Show Password Checkbox */
.show-password {
    margin-top: 10px;
}

.show-password input[type="checkbox"] {
    margin-right: 5px;
}

/* Error Messages */
.error-message {
    color: #d9534f;
    font-size: 0.875em;
    margin-top: 5px;
}

/* Submit Button */
button {
    background-color: #5bc0de;
    color: #fff;
    border: none;
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background-color: #31b0d5;
}

/* Error Summary */
.error-summary {
    margin-top: 20px;
    background-color: #f2dede;
    border: 1px solid #ebccd1;
    padding: 10px;
    border-radius: 4px;
    color: #a94442;
}

.error-summary ul {
    list-style: none;
    padding: 0;
}

.error-summary li {
    margin-bottom: 5px;
}

/* Responsive Styles */
@media (max-width: 600px) {
    .container {
        padding: 15px;
    }

    button {
        width: 100%;
        padding: 15px;
    }
}

</style>
<body>
    <div class="container">
        <div class="form-wrapper">
            <h1>Reset Your Password</h1>

            <form action="{{ url('api/resetPass/' . $token) }}" method="POST">
                @csrf

                <!-- New Password -->
                <div class="form-group">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" required>
                    <div class="show-password">
                        <input type="checkbox" id="showPassword" onclick="togglePasswordVisibility()">
                        <label for="showPassword">Show Password</label>
                    </div>

                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password:</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required>
              
                </div>

                <!-- Submit Button -->
                <button type="submit">Reset Password</button>

         
            </form>
        </div>
    </div>

    <!-- JavaScript to Toggle Password Visibility -->
    <script>
        function togglePasswordVisibility() {
            var password = document.getElementById('password');
            var passwordConfirmation = document.getElementById('password_confirmation');
            var showPassword = document.getElementById('showPassword');

            if (showPassword.checked) {
                password.type = 'text';
                passwordConfirmation.type = 'text';
            } else {
                password.type = 'password';
                passwordConfirmation.type = 'password';
            }
        }
    </script>
</body>
</html>
