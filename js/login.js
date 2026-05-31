const BASE = "/PersonalTracker";

if (isLoggedIn()) {

    window.location.href = BASE +
        "/app/dashboard.html";

}

const loginForm =
    document.getElementById(
        "loginForm"
    );

if (loginForm) {

    loginForm.addEventListener(
        "submit",
        async (e) => {

            e.preventDefault();

            const email =
                document.getElementById(
                    "email"
                ).value;

            const password =
                document.getElementById(
                    "password"
                ).value;

            try {

                const result =
                    await apiRequest(
                        "/auth/login",
                        {
                            method: "POST",

                            body:
                                JSON.stringify({
                                    email,
                                    password
                                })
                        }
                    );

                localStorage.setItem(
                    "jwt",
                    result.token
                );

                localStorage.setItem(
                    "user",
                    JSON.stringify(result.user)
                );

                localStorage.setItem(
                    "name",
                    result.user.name
                );

                localStorage.setItem(
                    "role",
                    result.user.role
                );

                localStorage.setItem(
                    "userId",
                    result.user.id
                );

                window.location.href = BASE +
                    "/app/dashboard.html";

            } catch (err) {

                alert(err.message);

            }

        }
    );

}