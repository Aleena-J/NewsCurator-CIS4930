import express from "express";
import cors from "cors";

const app = express();
const PORT = process.env.PORT || 4000;

app.use(cors());
app.use(express.json());

// Temporary sample data; replace with DAO/controller later
const sampleArticles = [
  {
    id: 1,
    title: "Welcome to NewsCurator",
    description: "This is a sample article served from the backend.",
    source: "NewsCurator Demo",
    publishedAt: new Date().toISOString()
  },
  {
    id: 2,
    title: "Getting Started",
    description: "Wire up your real news API here.",
    source: "NewsCurator Demo",
    publishedAt: new Date().toISOString()
  }
];

app.get("/api/health", (_req, res) => {
  res.json({ status: "ok", service: "news-curator-backend" });
});

app.get("/api/articles", (_req, res) => {
  res.json(sampleArticles);
});

app.listen(PORT, () => {
  console.log(`Backend listening on http://localhost:${PORT}`);
});

