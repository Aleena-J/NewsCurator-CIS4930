import { Router } from "express";
import { getArticles } from "../controller/ArticleController.js";

const router = Router();

router.get("/", getArticles);

export default router;
