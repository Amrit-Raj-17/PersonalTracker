async function loadComponent(id, path) {

    try {

        const response = await fetch(path);

        const html = await response.text();

        document.getElementById(id).innerHTML = html;

    } catch (err) {

        console.error(err);

    }

}

document.addEventListener("DOMContentLoaded", async () => {

    if (document.getElementById("header")) {

        await loadComponent(
            "header",
            "../components/header.html"
        );

        const role = localStorage.getItem("role");

        if (role === "ADMIN") {

            const adminNav = document.getElementById("adminNav");

            const visitNav = document.getElementById('visitNav');

            if (adminNav && visitNav) {

                adminNav.style.display = "inline-block";
                visitNav.style.display = "inline-block";

            }
        }
    }

    const logoutBtn =
        document.getElementById(
            "logoutBtn"
        );

    if (logoutBtn) {

        logoutBtn.addEventListener(
            "click",
            async () => {

                try {

                    const token =
                        localStorage.getItem(
                            "jwt"
                        );

                    if (token) {

                        await apiRequest(
                            "/visits/log",
                            {
                                method: "POST",

                                body: JSON.stringify({

                                    action:
                                        "LOGOUT",

                                    description:
                                        "User logged out"

                                })
                            }
                        );

                    }

                } catch (error) {

                    console.error(
                        error
                    );

                }

                localStorage.removeItem(
                    "jwt"
                );

                localStorage.removeItem(
                    "user"
                );

                window.location.href = 
                    "/login.html";

            }
        );

    }

    if (document.getElementById("footer")) {

        await loadComponent(
            "footer",
            "../components/footer.html"
        );

    }

});
