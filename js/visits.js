requireAuth();
requireAdmin();

let allLogs = [];

document.addEventListener(
    "DOMContentLoaded",
    async () => {

        setupEvents();

        await loadLogs();

    }
);

function setupEvents() {

    document
        .getElementById(
            "searchUser"
        )
        ?.addEventListener(
            "input",
            filterLogs
        );

    document
        .getElementById(
            "actionFilter"
        )
        ?.addEventListener(
            "change",
            filterLogs
        );

}

async function loadLogs() {

    try {

        allLogs =
            await apiRequest(
                "/visits"
            );

        renderLogs(allLogs);

    } catch (err) {

        console.error(err);

    }

}

function renderLogs(logs) {

    const container =
        document.getElementById(
            "logsContainer"
        );

    if (!container) return;

    container.innerHTML = "";

    if (logs.length === 0) {

        container.innerHTML = `
            <p>
                No activity found.
            </p>
        `;

        return;
    }

    logs.forEach(log => {

        container.innerHTML += `

        <div class="log-card">

            <div>

                <h3>
                    ${log.userId.name}
                </h3>

                <p>
                    ${log.action}
                </p>

            </div>

            <div>

                <small>
                    ${log.createdAt}
                </small>

            </div>

        </div>

        `;

    });

}

function filterLogs() {

    const search =
        document
        .getElementById(
            "searchUser"
        )
        .value
        .toLowerCase();

    const action =
        document
        .getElementById(
            "actionFilter"
        )
        .value;

    const filtered =
        allLogs.filter(log => {

            const matchesUser =
                log.userId.name
                .toLowerCase()
                .includes(search);

            const matchesAction =
                !action ||
                log.action === action;

            return (
                matchesUser &&
                matchesAction
            );

        });

    renderLogs(filtered);

}