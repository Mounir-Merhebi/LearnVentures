import { useState } from "react";
import Login from "../../Component/Content/Auth/Login";
import Register from "../../Component/Content/Auth/Register";

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
