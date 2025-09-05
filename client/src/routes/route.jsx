import { Routes, Route } from "react-router-dom";

import LandingPage from "../pages/LandingPage";
import Auth from "../pages/Auth";

const MyRoutes = () => {
  return (
    <Routes>
      <Route path="/" element={<LandingPage />} />
      <Route path="/auth" element={<Auth />} />
    </Routes>
  );
};

export default MyRoutes;