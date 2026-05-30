function getToken() {
    return localStorage.getItem("jwt");
}

function isLoggedIn() {
    return !!getToken();
}

function redirectToLogin() {
    window.location.href = "/app/login.html";
}

function requireAuth() {
    const token = getToken();

    if (!token) {
        redirectToLogin();
        return false;
    }

    return true;
}

function requireAdmin() {
    const role = localStorage.getItem("role");

    if (role !== "ADMIN") {
        window.location.href = "/app/dashboard.html";
        return false;
    }

    return true;
}