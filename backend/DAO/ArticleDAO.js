import { Article } from "../models/Article.js";

// For now this is a placeholder that returns static data.
// Later, swap this to fetch from a DB or an external news API.
export async function fetchAllArticles() {
  return [
    new Article({
      id: 1,
      title: "DAO placeholder article",
      description: "Replace this with real data from your data source.",
      source: "DAO Demo",
      publishedAt: new Date().toISOString()
    })
  ];
}
