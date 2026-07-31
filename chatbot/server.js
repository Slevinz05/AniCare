import "dotenv/config";
import express from "express";
import cors from "cors";
import { Mistral } from "@mistralai/mistralai";

const app = express();
app.use(cors());
app.use(express.json());

const SYMFONY_API = process.env.SYMFONY_API_URL || "http://localhost:8080";
const CHATBOT_API_KEY = process.env.CHATBOT_API_KEY || "";
const apiHeaders = { "X-Chatbot-Key": CHATBOT_API_KEY };

const client = new Mistral({ apiKey: process.env.MISTRAL_API_KEY });

// ── Définition des tools (function calling Mistral) ──

const tools = [
  {
    type: "function",
    function: {
      name: "search_professionals",
      description:
        "Recherche des professionnels equins dans la base de donnees AniCare. " +
        "Peut filtrer par specialite (Veterinaire, Dentiste, Osteopathe, Marechal-ferrant, " +
        "Shiatsu, Massotherapeute, Physiotherapeute, Nutritionniste, Comportementaliste), " +
        "par numero de departement (ex: 75, 33, 69), et/ou par recherche libre (nom, ville).",
      parameters: {
        type: "object",
        properties: {
          specialty: {
            type: "string",
            description: "Type de specialite du professionnel",
          },
          department: {
            type: "string",
            description: "Numero de departement (2-3 chiffres, ex: 75, 33, 971)",
          },
          query: {
            type: "string",
            description: "Recherche libre : nom, ville",
          },
        },
      },
    },
  },
  {
    type: "function",
    function: {
      name: "list_specialties",
      description:
        "Liste toutes les specialites de professionnels equins disponibles sur AniCare.",
      parameters: {
        type: "object",
        properties: {},
      },
    },
  },
];

// ── Exécution des tools ──

async function executeTool(name, args) {
  if (name === "search_professionals") {
    const params = new URLSearchParams();
    if (args.specialty) params.set("specialty", args.specialty);
    if (args.department) params.set("department", args.department);
    if (args.query) params.set("q", args.query);

    const url = `${SYMFONY_API}/api/professionals/chatbot?${params}`;
    const res = await fetch(url, { headers: apiHeaders });
    const data = await res.json();

    if (data.length === 0) {
      return JSON.stringify({
        found: 0,
        message: "Aucun professionnel trouve avec ces criteres.",
      });
    }

    return JSON.stringify({ found: data.length, professionals: data.slice(0, 5) });
  }

  if (name === "list_specialties") {
    const res = await fetch(`${SYMFONY_API}/api/professionals/specialties`, {
      headers: apiHeaders,
    });
    const data = await res.json();
    return JSON.stringify(data);
  }

  return JSON.stringify({ error: "Outil inconnu" });
}

// ── System prompt ──

const SYSTEM_PROMPT = `Tu es l'assistant AniCare, un chatbot specialise dans le monde equin.
Tu aides les proprietaires de chevaux a trouver le bon professionnel (veterinaire, dentiste, osteopathe, marechal-ferrant, etc.) en fonction de leur localisation et de leurs besoins.

Regles :
- Reponds toujours en francais.
- Sois chaleureux, concis et utile.
- Quand tu presentes des resultats, formate-les de maniere lisible avec le nom, la specialite, la ville et un lien vers la fiche.
- Les liens vers les fiches professionnelles sont de la forme : /annuaire/{id}
- Si l'utilisateur ne precise pas assez, pose des questions pour affiner (quelle specialite ? quel departement/ville ?).
- Tu ne peux chercher que des professionnels inscrits sur AniCare.
- N'invente jamais de professionnels, utilise uniquement les resultats de tes outils.
- Si aucun resultat, dis-le et suggere d'elargir les criteres.`;

// ── Conversations en mémoire ──

const conversations = new Map();

app.post("/api/chat", async (req, res) => {
  try {
    const { message, sessionId = "default" } = req.body;

    if (!message) {
      return res.status(400).json({ error: "Message requis" });
    }

    if (!conversations.has(sessionId)) {
      conversations.set(sessionId, []);
    }

    const history = conversations.get(sessionId);
    history.push({ role: "user", content: message });

    const messages = [{ role: "system", content: SYSTEM_PROMPT }, ...history];

    let response = await client.chat.complete({
      model: "mistral-small-latest",
      messages,
      tools,
      toolChoice: "auto",
    });

    let assistantMessage = response.choices[0].message;

    // Boucle de tool calling
    while (assistantMessage.toolCalls && assistantMessage.toolCalls.length > 0) {
      history.push(assistantMessage);

      for (const toolCall of assistantMessage.toolCalls) {
        const args =
          typeof toolCall.function.arguments === "string"
            ? JSON.parse(toolCall.function.arguments)
            : toolCall.function.arguments;

        const result = await executeTool(toolCall.function.name, args);

        history.push({
          role: "tool",
          name: toolCall.function.name,
          content: result,
          toolCallId: toolCall.id,
        });
      }

      response = await client.chat.complete({
        model: "mistral-small-latest",
        messages: [{ role: "system", content: SYSTEM_PROMPT }, ...history],
        tools,
        toolChoice: "auto",
      });

      assistantMessage = response.choices[0].message;
    }

    const textContent = assistantMessage.content || "";
    history.push({ role: "assistant", content: textContent });

    // Limiter l'historique
    if (history.length > 20) {
      history.splice(0, history.length - 20);
    }

    res.json({ response: textContent });
  } catch (err) {
    console.error("Chat error:", err);
    res.status(500).json({
      error: "Erreur du service. Verifiez votre cle API Mistral.",
    });
  }
});

app.delete("/api/chat/:sessionId", (req, res) => {
  conversations.delete(req.params.sessionId);
  res.json({ ok: true });
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
  console.log(`AniCare Chatbot running on http://localhost:${PORT}`);
});
