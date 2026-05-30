requireAuth();

let allTasks = [];

document.addEventListener(
    "DOMContentLoaded",
    async () => {

        setupEvents();

        await logPageVisit();

        await loadTasks();

    }
);

function setupEvents() {

    document
        .getElementById("createTaskBtn")
        .addEventListener(
            "click",
            openCreateModal
        );

    document
        .getElementById("closeModal")
        .addEventListener(
            "click",
            closeModal
        );

    document
        .getElementById("taskForm")
        .addEventListener(
            "submit",
            saveTask
        );

    document
        .getElementById("searchTask")
        .addEventListener(
            "input",
            filterTasks
        );

    document
        .getElementById("priorityFilter")
        .addEventListener(
            "change",
            filterTasks
        );

    document
        .getElementById("statusFilter")
        .addEventListener(
            "change",
            filterTasks
        );

}

async function loadTasks() {

    try {

        allTasks =
            await apiRequest(
                "/tasks"
            );

        renderTasks(allTasks);

    } catch (err) {

        console.error(err);

    }

}

function renderTasks(tasks) {

    const container =
        document.getElementById(
            "tasksContainer"
        );

    container.innerHTML = "";

    if (tasks.length === 0) {

        container.innerHTML =
            "<p>No tasks found.</p>";

        return;

    }

    tasks.forEach(task => {

        container.innerHTML += `

        <div class="task-card">

            <div class="task-header">

                <h2>
                    ${task.title}
                </h2>

                <span class="
                    priority
                    ${task.priority}
                ">
                    ${task.priority}
                </span>

            </div>

            <p>
                ${task.description}
            </p>

            <div class="progress-bar">

                <div
                    class="task-progress"
                    style="
                    width:${task.progress}%">
                </div>

            </div>

            <small>
                Progress:
                ${task.progress}%
            </small>

            <div class="task-info">

                <p>
                    Status:
                    ${task.status}
                </p>

                <p>
                    Deadline:
                    ${task.deadline}
                </p>

                <p>
                    Assigned By:
                    ${task.assignedBy}
                </p>

            </div>

            <div class="task-actions">

                <button
                    onclick="
                    editTask(
                    ${task.id}
                    )"
                >
                    Edit
                </button>

                <button
                    onclick="
                    deleteTask(
                    ${task.id}
                    )"
                >
                    Delete
                </button>

            </div>

        </div>

        `;

    });

}

function filterTasks() {

    const search =
        document
        .getElementById(
            "searchTask"
        )
        .value
        .toLowerCase();

    const priority =
        document
        .getElementById(
            "priorityFilter"
        )
        .value;

    const status =
        document
        .getElementById(
            "statusFilter"
        )
        .value;

    const filtered =
        allTasks.filter(task => {

            const matchesSearch =
                task.title
                .toLowerCase()
                .includes(search);

            const matchesPriority =
                !priority ||
                task.priority ===
                priority;

            const matchesStatus =
                !status ||
                task.status ===
                status;

            return (
                matchesSearch &&
                matchesPriority &&
                matchesStatus
            );

        });

    renderTasks(filtered);

}

function openCreateModal() {

    document
        .getElementById(
            "taskForm"
        )
        .reset();

    document
        .getElementById(
            "taskId"
        )
        .value = "";

    document
        .getElementById(
            "modalTitle"
        )
        .textContent =
        "Create Task";

    document
        .getElementById(
            "taskModal"
        )
        .classList
        .remove("hidden");

}

function closeModal() {

    document
        .getElementById(
            "taskModal"
        )
        .classList
        .add("hidden");

}

function editTask(id) {

    const task =
        allTasks.find(
            t => t.id === id
        );

    if (!task) return;

    document
        .getElementById(
            "taskId"
        )
        .value = task.id;

    document
        .getElementById(
            "title"
        )
        .value = task.title;

    document
        .getElementById(
            "description"
        )
        .value =
        task.description;

    document
        .getElementById(
            "priority"
        )
        .value =
        task.priority;

    document
        .getElementById(
            "deadline"
        )
        .value =
        task.deadline;

    document
        .getElementById(
            "progress"
        )
        .value =
        task.progress;

    document
        .getElementById(
            "status"
        )
        .value =
        task.status;

    document
        .getElementById(
            "modalTitle"
        )
        .textContent =
        "Edit Task";

    document
        .getElementById(
            "taskModal"
        )
        .classList
        .remove("hidden");

}

async function saveTask(e) {

    e.preventDefault();

    const taskId =
        document
        .getElementById(
            "taskId"
        )
        .value;

    const taskData = {

        title:
            document
            .getElementById(
                "title"
            ).value,

        description:
            document
            .getElementById(
                "description"
            ).value,

        priority:
            document
            .getElementById(
                "priority"
            ).value,

        deadline:
            document
            .getElementById(
                "deadline"
            ).value,

        progress:
            Number(
                document
                .getElementById(
                    "progress"
                ).value
            ),

        status:
            document
            .getElementById(
                "status"
            ).value

    };

    console.log(taskData);

    if(taskId){

        await apiRequest(
            `/tasks/${taskId}`,
            {
                method:"PUT",
                body:JSON.stringify(taskData)
            }
        );

    } else {

        await apiRequest(
            "/tasks",
            {
                method:"POST",
                body:JSON.stringify(taskData)
            }
        );

    }

    closeModal();

}

async function deleteTask(id) {

    const confirmDelete =
        confirm(
            "Delete task?"
        );

    if (!confirmDelete)
        return;

    await apiRequest(
        `/tasks/${id}`,
        {
            method:"DELETE"
        }
    );

    allTasks =
        allTasks.filter(
            t => t.id !== id
        );

    renderTasks(allTasks);

}

async function logPageVisit() {

    try {

        await apiRequest(
            "/visits/log",
            {
                method:"POST",
                body:JSON.stringify({
                    action:
                    "OPEN_TASKS"
                })
            }
        );

    } catch (err) {

        console.error(err);

    }

}