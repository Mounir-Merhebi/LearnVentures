import { useState } from "react";
import Login from "../../components/content/Auth/login";
import Register from "../../components/content/Auth/register";

const Auth = () => {
  const [isLogin, setIsLogin] = useState(true);

  const switchForm = () => {
    setIsLogin(!isLogin);
  };

  return (
    <div className="auth-page">
      <div className="auth-box">
        {isLogin ? (
          <Login toggle={switchForm} />
        ) : (
          <Register toggle={switchForm} />
        )}
      </div>
    </div>
  );
};

export default Auth;
