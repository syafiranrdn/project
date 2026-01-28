const express = require("express");
const app = express();

// IMPORT DB
const db = require("./db");

// ROOT TEST (API HIDUP)
app.get("/", (req, res) => {
  res.json({
    ok: true,
    message: "API running (Node)"
  });
});

// DB CONNECTION TEST
app.get("/health/db", async (req, res) => {
  try {
    await db.query("SELECT 1");
    res.json({
      ok: true,
      db: "connected"
    });
  } catch (err) {
    res.status(500).json({
      ok: false,
      error: err.message
    });
  }
});

// START SERVER
const PORT = process.env.PORT || 8080;
app.listen(PORT, () => {
  console.log("Server running on port", PORT);
});
