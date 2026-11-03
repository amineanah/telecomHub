import { getApp } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-app.js";
import { getFunctions, httpsCallable } from "https://www.gstatic.com/firebasejs/12.4.0/firebase-functions.js";

let cachedUsers = null;
const $ = id => document.getElementById(id);
const formatDate = value => {
  if (!value) return "Never";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? "Unknown" : date.toLocaleString("en-GB");
};
const monthKey = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,"0")}`;

async function fetchUsers() {
  const functions = getFunctions(getApp(), "us-central1");
  const listUsers = httpsCallable(functions, "adminListUsers");
  const users = [];
  let pageToken = null;
  do {
    const response = await listUsers({ pageToken, pageSize: 1000 });
    users.push(...(response.data.users || []));
    pageToken = response.data.nextPageToken || null;
  } while (pageToken);
  return users;
}

function renderChart(users) {
  const chart = $("users-monthly-chart");
  chart.replaceChildren();
  const now = new Date();
  const months = [];
  for (let offset=11; offset>=0; offset--) {
    const date = new Date(now.getFullYear(), now.getMonth()-offset, 1);
    const key = monthKey(date);
    months.push({
      key,
      label: date.toLocaleDateString("en-GB", { month:"short", year:"2-digit" }),
      count: users.filter(user => user.createdAt && monthKey(new Date(user.createdAt)) === key).length
    });
  }
  const max = Math.max(1, ...months.map(item => item.count));
  months.forEach(item => {
    const column = document.createElement("div");
    column.className = "user-month-column";
    const count = document.createElement("strong");
    count.textContent = String(item.count);
    const track = document.createElement("div");
    track.className = "user-month-track";
    const bar = document.createElement("span");
    bar.style.height = `${Math.max(item.count ? 5 : 0, item.count/max*100)}%`;
    track.append(bar);
    const label = document.createElement("small");
    label.textContent = item.label;
    column.append(count, track, label);
    chart.append(column);
  });
  const currentKey = monthKey(now);
  const current = months.find(item => item.key === currentKey);
  $("users-month-total").textContent = String(current?.count || 0);
}

function renderTable() {
  const query = $("users-search").value.trim().toLowerCase();
  const filtered = (cachedUsers || []).filter(user =>
    (user.name || "").toLowerCase().includes(query) ||
    (user.email || "").toLowerCase().includes(query)
  );
  const body = $("users-table-body");
  body.replaceChildren();
  if (!filtered.length) {
    const row = document.createElement("tr");
    const cell = document.createElement("td");
    cell.colSpan = 4;
    cell.textContent = query ? "No accounts match your search." : "No registered accounts found.";
    row.append(cell);
    body.append(row);
    return;
  }
  filtered.sort((a,b) => new Date(b.createdAt || 0) - new Date(a.createdAt || 0));
  filtered.forEach(user => {
    const row = document.createElement("tr");
    [user.name || "Not provided", user.email || "No email", formatDate(user.createdAt), formatDate(user.lastLogin)].forEach(value => {
      const cell = document.createElement("td");
      cell.textContent = value;
      row.append(cell);
    });
    body.append(row);
  });
  $("users-status").textContent = `Showing ${filtered.length.toLocaleString()} of ${cachedUsers.length.toLocaleString()} registered accounts.`;
}

function csvCell(value) {
  let text = String(value ?? "");
  if (/^[=+@\-\t\r]/.test(text)) text = "'" + text;
  return '"' + text.replace(/"/g, '""') + '"';
}
function exportCsv() {
  const rows = [
    ["UID","Name","Email","Account created","Last login"],
    ...(cachedUsers || []).map(user => [user.uid,user.name,user.email,user.createdAt,user.lastLogin])
  ];
  const csv = "\uFEFF" + rows.map(row => row.map(csvCell).join(",")).join("\r\n");
  const url = URL.createObjectURL(new Blob([csv], {type:"text/csv;charset=utf-8"}));
  const link = document.createElement("a");
  link.href = url;
  link.download = "telecomhub-registered-users.csv";
  document.body.append(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

async function load() {
  $("users-status").textContent = "Loading registered Firebase accounts…";
  $("users-refresh").disabled = true;
  try {
    cachedUsers = await fetchUsers();
    $("users-total").textContent = cachedUsers.length.toLocaleString();
    renderChart(cachedUsers);
    renderTable();
  } catch (error) {
    console.error(error);
    $("users-status").textContent = error?.message || "Unable to load accounts. Check the deployed Cloud Function and administrator access.";
  } finally {
    $("users-refresh").disabled = false;
  }
}

export function initUserManagement() {
  $("users-refresh").addEventListener("click", load);
  $("users-search").addEventListener("input", renderTable);
  $("users-export").addEventListener("click", () => {
    if (!cachedUsers) {
      $("users-status").textContent = "Load the user list before exporting.";
      return;
    }
    exportCsv();
  });
  load();
}
