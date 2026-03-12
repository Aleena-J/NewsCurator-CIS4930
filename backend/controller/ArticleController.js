import { fetchAllArticles } from "../DAO/ArticleDAO.js";

export async function getArticles(_req, res, next) {
  try {
    const articles = await fetchAllArticles();
    res.json(articles);
  } catch (err) {
    next(err);
  }
}
