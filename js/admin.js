requireAuth();
requireAdmin();

let users = [];

document.addEventListener(
    "DOMContentLoaded",
    async () => {

        setupEvents();

        await loadDashboard();

        await loadUsers();

    }
);

function setupEvents() {

    document
        .getElementById(
            "createUserBtn"
        )
        ?.addEventListener(
            "click",
            openUserModal
        );

    document
        .getElementById(
            "closeUserModal"
        )
        ?.addEventListener(
            "click",
            closeUserModal
        );

    document
        .getElementById(
            "userForm"
        )
        ?.addEventListener(
            "submit",
            saveUser
        );

}

async function loadDashboard() {

    const data = {


    };

    document
        .getElementById(
            "totalUsers"
        )
        .textContent =
        data.totalUsers;

    document
        .getElementById(
            "totalTasks"
        )
        .textContent =
        data.totalTasks;

    document
        .getElementById(
            "totalNotes"
        )
        .textContent =
        data.totalNotes;

    document
        .getElementById(
            "activeUsers"
        )
        .textContent =
        data.activeUsers;

}

async function loadUsers() {

    users = [

        

    ];

    renderUsers();

}

function renderUsers() {

    const container =
        document.getElementById(
            "usersContainer"
        );

    container.innerHTML = "";

    users.forEach(user => {

        container.innerHTML += `

        <div class="user-card">

            <div>

                <h3>
                    ${user.name}
                </h3>

                <p>
                    ${user.email}
                </p>

                <small>
                    ${user.role}
                </small>

            </div>

            <div>

                <button
                    onclick="
                    toggleRole(
                        ${user.id}
                    )
                    "
                >
                    Change Role
                </button>

                <button
                    onclick="
                    deleteUser(
                        ${user.id}
                    )
                    "
                >
                    Delete
                </button>

            </div>

        </div>

        `;

    });

}

function openUserModal() {

    document
        .getElementById(
            "userForm"
        )
        .reset();

    document
        .getElementById(
            "userModal"
        )
        .classList
        .remove("hidden");

}

function closeUserModal() {

    document
        .getElementById(
            "userModal"
        )
        .classList
        .add("hidden");

}

function saveUser(e) {

    e.preventDefault();

    const user = {

        id: Date.now(),

        name:
            document
            .getElementById(
                "userName"
            )
            .value,

        email:
            document
            .getElementById(
                "userEmail"
            )
            .value,

        role:
            document
            .getElementById(
                "userRole"
            )
            .value

    };

    users.push(user);

    renderUsers();

    closeUserModal();

}

function toggleRole(id) {

    const user =
        users.find(
            u => u.id === id
        );

    if (!user) return;

    user.role =
        user.role === "ADMIN"
        ? "USER"
        : "ADMIN";

    renderUsers();

}

function deleteUser(id) {

    const confirmDelete =
        confirm(
            "Delete user?"
        );

    if (!confirmDelete)
        return;

    users =
        users.filter(
            user =>
            user.id !== id
        );

    renderUsers();

}